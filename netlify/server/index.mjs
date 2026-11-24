import { randomUUID } from 'node:crypto';
import { getIdentityConfig, getUser, verifyRequestOrigin } from '@netlify/identity';
import { and, asc, count, desc, eq, ilike, inArray, or, sql } from 'drizzle-orm';
import { Hono } from 'hono';
import { bodyLimit } from 'hono/body-limit';
import { getCookie } from 'hono/cookie';
import { getDb } from '../../db/index.ts';
import { history, notifications, reports, staff, staffRoles, students, trainers } from '../../db/schema.ts';
import { authPage, casePage, dashboard, directory, home, newReport, page, profilePage, reportsPage } from './views.mjs';
import { InputError, input, listReports, reportFields, saveReview } from './workflow.mjs';

const uuidPattern = /^[\da-f]{8}(?:-[\da-f]{4}){3}-[\da-f]{12}$/i;
const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
const publicPaths = new Map([['/login.php', 'student'], ['/admin/login.php', 'staff'], ['/register.php', 'signup'], ['/recover', 'recover']]);
const reportPagePath = '/report_details.php';
const staffCasePath = '/admin/report_view.php';
const adminReportsPath = '/admin/reports.php';
const profilePath = '/profile.php';

async function readIdentity(context) {
  const user = await getUser();
  if (user?.confirmedAt) return user;

  const token = getCookie(context, 'nf_jwt');
  const identityUrl = getIdentityConfig()?.url;
  if (!identityUrl || !token || !/^[\w-]+\.[\w-]+\.[\w-]+$/.test(token)) return null;
  try {
    const response = await fetch(`${identityUrl}/user`, {
      headers: { Authorization: `Bearer ${token}` }, redirect: 'error', signal: AbortSignal.timeout(4000),
    });
    if (!response.ok) return null;
    const verified = await response.json();
    return {
      id: verified.id, email: verified.email, confirmedAt: verified.confirmed_at,
      name: verified.user_metadata?.full_name, roles: verified.app_metadata?.roles, appMetadata: verified.app_metadata,
    };
  } catch {
    return null;
  }
}

function missing(context) {
  return context.html(page('Page not found', '<p>This page or report could not be found.</p>', context.get('viewer')), 404);
}

function forbidden(context) {
  return context.html(page('Access denied', '<p>Your account does not have permission to use this page.</p>', context.get('viewer')), 403);
}

function uniqueViolation(error) {
  return error?.code === '23505' || error?.cause?.code === '23505';
}

function reportId(value) {
  return /^\d{1,10}$/.test(value ?? '') && Number(value) > 0 && Number(value) <= 2147483647 ? Number(value) : null;
}

function isStaff(viewer) {
  return viewer?.role === 'admin' || viewer?.role === 'staff';
}

async function getCase(db, id, studentId) {
  if (!id) return null;
  const [report] = await db.select().from(reports).where(and(eq(reports.id, id), studentId ? eq(reports.studentId, studentId) : undefined)).limit(1);
  return report;
}

async function showCase(context, db, report, staffView, success, error) {
  const changes = await db.select().from(history).where(eq(history.reportId, report.id)).orderBy(desc(history.id));
  return context.html(casePage(context.get('viewer'), report, changes, staffView, success, error), error ? 422 : 200);
}

async function dashboardData(db, profile) {
  const owner = profile ? eq(reports.studentId, profile.id) : undefined;
  const [counts, recent, notices, breakdown, leadingTrainers] = await Promise.all([
    db.select({ status: reports.status, total: count() }).from(reports).where(owner).groupBy(reports.status),
    db.select().from(reports).where(owner).orderBy(desc(reports.id)).limit(6),
    profile ? db.select().from(notifications).where(eq(notifications.studentId, profile.id)).orderBy(desc(notifications.id)).limit(12) : [],
    profile ? [] : db.select({ semester: reports.semester, total: count() }).from(reports).groupBy(reports.semester).orderBy(reports.semester),
    profile ? [] : db.select({ trainer: reports.trainer, total: count() }).from(reports).groupBy(reports.trainer).orderBy(desc(count()), asc(reports.trainer)).limit(5),
  ]);
  return { totals: Object.fromEntries(counts.map(({ status, total }) => [status, total])), recent, notices, breakdown, leadingTrainers, profile };
}

export function createPortal({ database = getDb, identity = readIdentity } = {}) {
  const app = new Hono();

  app.use('*', async (context, next) => {
    context.header('Cache-Control', 'private, no-store');
    context.header('X-Content-Type-Options', 'nosniff');
    context.header('Referrer-Policy', 'same-origin');
    context.header('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; img-src 'self' data:; object-src 'none'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
    await next();
  });

  app.on('POST', '*', async (context, next) => {
    try {
      verifyRequestOrigin(context.req.raw);
    } catch {
      return forbidden(context);
    }
    return next();
  }, bodyLimit({ maxSize: 30000 }));

  app.use('*', async (context, next) => {
    const user = await identity(context);
    if (user?.confirmedAt && typeof user.id === 'string' && uuidPattern.test(user.id) && typeof user.email === 'string' && emailPattern.test(user.email)) {
      const email = user.email.toLowerCase();
      const roles = user.roles ?? user.appMetadata?.authorization?.roles;
      if (Array.isArray(roles) && roles.includes('admin')) {
        context.set('viewer', { id: user.id, email, name: user.name, role: 'admin' });
      } else {
        const [access] = await database().select().from(staff).where(eq(staff.email, email)).limit(1);
        if (access && !access.active) return forbidden(context);
        context.set('viewer', { id: user.id, email, name: access?.fullName ?? user.name, role: access ? 'staff' : 'student' });
      }
    }
    return next();
  });

  app.get('/', (context) => context.html(home(context.get('viewer'))));
  for (const [path, kind] of publicPaths) app.get(path, (context) => context.html(authPage(kind)));
  app.get('/index.php', (context) => context.redirect('/'));

  function requireViewer(portal, needsProfile = false) {
    return async (context, next) => {
      const viewer = context.get('viewer');
      if (!viewer) return context.redirect(portal === 'staff' ? '/admin/login.php' : '/login.php', 303);
      if (portal === 'staff' && !isStaff(viewer)) return forbidden(context);
      if (portal === 'student' && viewer.role !== 'student') return context.redirect('/admin/dashboard.php', 303);
      if (needsProfile || context.req.path === profilePath) {
        const [profile] = await database().select().from(students).where(eq(students.id, viewer.id)).limit(1);
        if (needsProfile && !profile) return context.redirect(profilePath, 303);
        context.set('profile', profile);
      }
      return next();
    };
  }

  const studentOnly = requireViewer('student');
  const studentProfile = requireViewer('student', true);
  const staffOnly = requireViewer('staff');

  app.get(profilePath, studentOnly, (context) => context.html(profilePage(context.get('viewer'), context.get('profile'), undefined, context.req.query('saved') === '1')));
  app.post(profilePath, studentOnly, async (context) => {
    const viewer = context.get('viewer');
    const profile = context.get('profile');
    const form = await context.req.parseBody();
    try {
      const fields = {
        fullName: input(form, 'full_name', 150, true), course: input(form, 'course'), phone: input(form, 'phone', 35),
      };
      if (profile) await database().update(students).set(fields).where(eq(students.id, viewer.id));
      else await database().insert(students).values({ ...fields, id: viewer.id, email: viewer.email, admissionNo: input(form, 'admission_no', 50, true).toUpperCase() });
      return context.redirect(profile ? `${profilePath}?saved=1` : '/dashboard.php', 303);
    } catch (error) {
      if (error instanceof InputError || uniqueViolation(error)) {
        return context.html(profilePage(viewer, profile, uniqueViolation(error) ? 'That admission number or email is already registered.' : error.message), 422);
      }
      throw error;
    }
  });

  app.get('/dashboard.php', studentProfile, async (context) => context.html(dashboard(context.get('viewer'), await dashboardData(database(), context.get('profile')), false)));
  app.get('/admin/dashboard.php', staffOnly, async (context) => context.html(dashboard(context.get('viewer'), await dashboardData(database()), true)));

  const trainerList = () => database().select().from(trainers).orderBy(asc(trainers.name));
  app.get('/report_new.php', studentProfile, async (context) => context.html(newReport(context.get('viewer'), context.get('profile'), await trainerList())));
  app.post('/actions/submit_report.php', studentProfile, async (context) => {
    const viewer = context.get('viewer');
    const profile = context.get('profile');
    const form = await context.req.parseBody();
    try {
      const fields = reportFields(form);
      const id = await database().transaction(async (transaction) => {
        await transaction.insert(trainers).values({ name: fields.trainer }).onConflictDoNothing();
        const reference = `MM-${new Date().getUTCFullYear()}-${randomUUID().replaceAll('-', '').slice(0, 12).toUpperCase()}`;
        const [report] = await transaction.insert(reports).values({
          ...fields, reference, studentId: viewer.id, studentName: profile.fullName, admissionNo: profile.admissionNo,
        }).returning({ id: reports.id });
        await transaction.insert(history).values({ reportId: report.id, status: 'Pending', remarks: 'Report submitted by student.', actorId: viewer.id });
        return report.id;
      });
      return context.redirect(`/report_success.php?id=${id}`, 303);
    } catch (error) {
      if (!(error instanceof InputError)) throw error;
      return context.html(newReport(viewer, profile, await trainerList(), error.message, form), 422);
    }
  });

  app.get('/my_reports.php', studentProfile, async (context) => {
    const query = context.req.query();
    return context.html(reportsPage(context.get('viewer'), await listReports(database(), query, context.get('viewer').id), query, false));
  });
  app.get(adminReportsPath, staffOnly, async (context) => {
    const query = context.req.query();
    return context.html(reportsPage(context.get('viewer'), await listReports(database(), query), query, true));
  });

  for (const path of [reportPagePath, '/report_success.php']) {
    app.get(path, studentProfile, async (context) => {
      const db = database();
      const report = await getCase(db, reportId(context.req.query('id')), context.get('viewer').id);
      return report ? showCase(context, db, report, false, path !== reportPagePath) : missing(context);
    });
  }

  app.post('/actions/mark_notifications_read.php', studentProfile, async (context) => {
    await database().update(notifications).set({ read: true }).where(and(eq(notifications.studentId, context.get('viewer').id), eq(notifications.read, false)));
    return context.redirect('/dashboard.php', 303);
  });

  app.get(staffCasePath, staffOnly, async (context) => {
    const db = database();
    const report = await getCase(db, reportId(context.req.query('id')));
    return report ? showCase(context, db, report, true) : missing(context);
  });

  app.post('/admin/actions/update_report.php', staffOnly, async (context) => {
    const db = database();
    const id = reportId(context.req.query('id'));
    if (!id) return missing(context);
    const form = await context.req.parseBody();
    try {
      const found = await db.transaction(async (transaction) => {
        const [report] = await transaction.select().from(reports).where(eq(reports.id, id)).for('update');
        if (!report) return false;
        await saveReview(transaction, report, form, context.get('viewer').id);
        return true;
      });
      return found ? context.redirect(`${staffCasePath}?id=${id}`, 303) : missing(context);
    } catch (error) {
      if (!(error instanceof InputError)) throw error;
      const report = await getCase(db, id);
      return report ? showCase(context, db, report, true, false, error.message) : missing(context);
    }
  });

  app.post('/admin/actions/bulk_update.php', staffOnly, async (context) => {
    const db = database();
    const form = await context.req.parseBody({ all: true });
    const ids = [...new Set([form.report_id].flat().map(reportId).filter(Boolean))];
    try {
      if (!ids.length || ids.length > 15 || !['In Review', 'Resolved', 'Rejected'].includes(form.status)) {
        throw new InputError('Select between one and 15 cases and a valid action.');
      }
      await db.transaction(async (transaction) => {
        const selected = await transaction.select().from(reports).where(inArray(reports.id, ids)).orderBy(asc(reports.id)).for('update');
        if (selected.length !== ids.length) throw new InputError('One or more cases could not be found.');
        for (const report of selected) {
          await saveReview(transaction, report, { status: form.status, remarks: report.remarks }, context.get('viewer').id);
        }
      });
      return context.redirect(adminReportsPath, 303);
    } catch (error) {
      if (!(error instanceof InputError)) throw error;
      return context.html(reportsPage(context.get('viewer'), await listReports(db, {}), {}, true, error.message), 422);
    }
  });

  app.get('/admin/students.php', staffOnly, async (context) => {
    const query = context.req.query();
    const number = Math.min(10000, Math.max(1, Number.parseInt(query.page, 10) || 1));
    const match = query.q ? `%${query.q.slice(0, 100).replace(/[\\%_]/g, '\\$&')}%` : null;
    const list = await database().select().from(students)
      .where(match ? or(ilike(students.fullName, match), ilike(students.admissionNo, match)) : undefined)
      .orderBy(asc(students.fullName), asc(students.id)).limit(16).offset((number - 1) * 15);
    return context.html(directory(context.get('viewer'), 'Students', list.slice(0, 15), query, number, list.length > 15));
  });

  app.get('/admin/trainers.php', staffOnly, async (context) => context.html(directory(context.get('viewer'), 'Trainers', await trainerList())));
  app.post('/admin/trainers.php', staffOnly, async (context) => {
    const form = await context.req.parseBody();
    try {
      const name = input(form, 'name', 150, true);
      if (form.intent === 'remove') await database().delete(trainers).where(eq(trainers.name, name));
      else await database().insert(trainers).values({ name }).onConflictDoNothing();
      return context.redirect('/admin/trainers.php', 303);
    } catch (error) {
      if (!(error instanceof InputError)) throw error;
      return context.html(directory(context.get('viewer'), 'Trainers', await trainerList(), {}, 1, false, error.message), 422);
    }
  });

  const staffList = () => database().select().from(staff).orderBy(asc(staff.fullName));
  app.get('/admin/staff.php', staffOnly, async (context) => context.html(directory(context.get('viewer'), 'Staff', await staffList())));
  app.post('/admin/staff.php', staffOnly, async (context) => {
    if (context.get('viewer').role !== 'admin') return forbidden(context);
    const form = await context.req.parseBody();
    try {
      const email = input(form, 'email', 254, true).toLowerCase();
      if (!emailPattern.test(email)) throw new InputError('Enter a valid staff email address.');
      if (form.intent === 'toggle') {
        await database().update(staff).set({ active: sql`not ${staff.active}` }).where(eq(staff.email, email));
      } else {
        const role = input(form, 'role', 40, true);
        if (!staffRoles.includes(role)) throw new InputError('Select a valid staff role.');
        await database().insert(staff).values({ email, role, fullName: input(form, 'full_name', 150, true), staffNo: input(form, 'staff_no', 50, true), department: input(form, 'department') });
      }
      return context.redirect('/admin/staff.php', 303);
    } catch (error) {
      if (!(error instanceof InputError) && !uniqueViolation(error)) throw error;
      return context.html(directory(context.get('viewer'), 'Staff', await staffList(), {}, 1, false, uniqueViolation(error) ? 'That staff email or staff number is already listed.' : error.message), 422);
    }
  });

  app.notFound(missing);
  app.onError((error, context) => {
    console.error('The portal could not complete a request.', error.name);
    return context.html(page('Something went wrong', '<p>The portal could not complete your request. Please try again.</p>', context.get('viewer')), 500);
  });
  return app;
}

export default createPortal();
