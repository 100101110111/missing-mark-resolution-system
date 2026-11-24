import { assessments, grades, semesters, staffRoles, statuses } from '../../db/schema.ts';
import { openAwards } from './workflow.mjs';

const brand = 'Kenya School of TVET';
const icon = (name) => `<i class="fa-solid fa-${name}" aria-hidden="true"></i>`;
const escapes = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
export const escape = (value) => String(value ?? '').replace(/[&<>"']/g, (character) => escapes[character]);
const button = (label, path, variant = 'primary') => `<a href="${escape(path)}" class="btn btn-kt-${variant}">${label}</a>`;
const card = (inner) => `<section class="kt-card-flat p-4 mb-4">${inner}</section>`;
const alert = (message, kind = 'danger') => `<p class="alert alert-${kind}" role="status">${escape(message)}</p>`;
const paragraph = (text) => `<p class="kt-muted">${escape(text)}</p>`;
const submit = (text) => `<button class="btn btn-kt-primary" type="submit">${text}</button>`;
const date = (value) => new Intl.DateTimeFormat('en-KE', { dateStyle: 'medium', timeZone: 'Africa/Nairobi' }).format(value);

export function field(label, name, { value = '', type = 'text', max = 150, required = false, attributes = '' } = {}) {
  return `<label class="d-block mb-3"><span class="form-label d-block">${escape(label)}</span><input class="form-control" name="${name}" type="${type}" value="${escape(value)}" maxlength="${max}" ${required ? 'required' : ''} ${attributes}></label>`;
}

function select(label, name, options, current, placeholder = '', required = false) {
  return `<label class="d-block mb-3"><span class="form-label d-block">${escape(label)}</span><select class="form-select" name="${name}" ${required ? 'required' : ''}>${placeholder ? `<option value="">${escape(placeholder)}</option>` : ''}${options.map((option) => `<option${option === current ? ' selected' : ''}>${escape(option)}</option>`).join('')}</select></label>`;
}

const area = (label, name, value = '') => `<label class="d-block mb-3"><span class="form-label d-block">${label}</span><textarea class="form-control" name="${name}" rows="3" maxlength="4000">${escape(value)}</textarea></label>`;
const columns = (...cells) => `<div class="row g-3">${cells.map((cell) => `<div class="col-md">${cell}</div>`).join('')}</div>`;
const heading = (title, subtitle = '') => `<div class="mb-4"><div class="kt-divider-gold mb-3"></div><h1 class="kt-section-title h2">${escape(title)}</h1>${subtitle ? paragraph(subtitle) : ''}</div>`;

export function layout(title, content, viewer) {
  const links = viewer?.role === 'admin' || viewer?.role === 'staff'
    ? [['Dashboard', '/admin/dashboard.php'], ['Reports', '/admin/reports.php'], ['Students', '/admin/students.php'], ['Trainers', '/admin/trainers.php'], ['Staff', '/admin/staff.php']]
    : viewer ? [['Dashboard', '/dashboard.php'], ['My reports', '/my_reports.php'], ['New report', '/report_new.php'], ['Profile', '/profile.php']]
      : [['Student login', '/login.php'], ['Register', '/register.php'], ['Staff portal', '/admin/login.php']];
  return `<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>${escape(title)} | ${brand}</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet"><link rel="stylesheet" href="/assets/style.css"><script type="module" src="/assets/portal.js"></script></head>
    <body class="d-flex flex-column min-vh-100"><header class="kt-navbar"><nav class="container py-3 d-flex align-items-center justify-content-between flex-wrap gap-2" aria-label="Main navigation">
    <a class="kt-brand text-success" href="/"><span class="kt-brand-badge">${icon('graduation-cap')}</span><span>${brand}</span></a>
    <div class="d-flex flex-wrap align-items-center gap-3">${links.map(([label, path]) => `<a class="nav-link" href="${path}">${label}</a>`).join('')}${viewer ? '<button class="btn btn-sm btn-kt-soft" type="button" data-logout>Sign out</button>' : ''}</div></nav></header>
    <main class="flex-grow-1">${content}</main><footer class="kt-footer py-4"><div class="container small">${brand} · Missing Marks Resolution System</div></footer></body></html>`;
}

export const page = (title, body, viewer, subtitle) => layout(title, `<div class="container py-5">${heading(title, subtitle)}${body}</div>`, viewer);

export function home(viewer) {
  return layout('Missing Marks Resolution System', `<section class="kt-hero py-5"><div class="container kt-hero-inner py-lg-5"><div class="row align-items-center g-5"><div class="col-lg-7"><span class="kt-pill mb-3">${icon('graduation-cap')} Academic Services Portal</span><h1>Missing Marks<br><span class="text-warning">Resolution System</span></h1><p class="lead my-4">Report a missing assessment result and follow its progress, from submission through verification to resolution.</p><div class="d-flex gap-3 flex-wrap">${viewer ? button('Open dashboard', '/dashboard.php', 'gold') : `${button('Student portal', '/login.php', 'gold')} ${button('Staff portal', '/admin/login.php', 'outline')}`}</div></div>
    <div class="col-lg-5"><div class="kt-hero-art p-4"><span class="kt-pill mb-3">Case workflow</span><h2 class="h4 mb-3">Know where your report stands</h2><div class="kt-steps"><p class="kt-step">Submit your assessment details</p><p class="kt-step">The registrar’s office verifies the case</p><p class="kt-step mb-0">Track the decision and any awards</p></div></div></div></div></div></section>
    <section class="container py-5"><div class="row g-4">${[['file-circle-plus', 'Submit a report', 'Tell us which CAT or End Term result is missing.'], ['route', 'Track your progress', 'A reference number and status timeline keep every case easy to find.'], ['bell', 'Get updates', 'Changes appear in your student notifications.']].map(([symbol, title, detail]) => `<div class="col-md-4"><article class="kt-card p-4 h-100"><div class="kt-feature-icon mb-3">${icon(symbol)}</div><h2 class="h5">${title}</h2>${paragraph(detail)}</article></div>`).join('')}</div></section>`, viewer);
}

export function authPage(kind) {
  const titles = { student: 'Student sign in', staff: 'Staff sign in', signup: 'Create a student account', recover: 'Recover your password' };
  const formType = { student: 'login', staff: 'login', signup: 'signup', recover: 'recover' }[kind];
  const email = field('Email address', 'email', { type: 'email', max: 254, required: true, attributes: 'autocomplete="email"' });
  const password = field('Password', 'password', { type: 'password', required: true, attributes: `autocomplete="${kind === 'signup' ? 'new-password' : 'current-password'}" minlength="${kind === 'signup' ? 8 : 1}"` });
  const extra = kind === 'signup' ? field('Full name', 'full_name', { required: true, attributes: 'autocomplete="name"' }) : '';
  const links = kind === 'student' ? '<a href="/register.php">Create an account</a> · <a href="/recover">Forgot password?</a>' : '<a href="/login.php">Student sign in</a> · <a href="/recover">Forgot password?</a>';
  return layout(titles[kind], `<div class="kt-auth-wrap"><div class="kt-auth-side d-none d-lg-flex align-items-center px-5"><div class="content"><span class="kt-pill mb-3">${brand}</span><h2 class="display-5">Missing Marks<br>Resolution System</h2></div></div><section class="kt-auth-form"><div class="kt-form-card">${heading(titles[kind])}<p data-auth-notice role="status" hidden></p><form data-auth="${formType}" data-staff="${kind === 'staff'}" method="post">${extra}${email}${kind === 'recover' ? '' : password}${submit(kind === 'signup' ? 'Create account' : kind === 'recover' ? 'Send recovery email' : 'Sign in')}</form><p class="mt-4 small">${links}</p></div></section></div>`);
}

export function profilePage(viewer, profile, error, saved) {
  return page(profile ? 'My profile' : 'Complete your student profile', card(`${error ? alert(error) : ''}${saved ? alert('Profile saved.', 'success') : ''}<form method="post" action="/profile.php">
    ${field('Verified email address', 'email', { value: viewer.email, type: 'email', attributes: 'disabled' })}
    ${columns(field('Full name', 'full_name', { value: profile?.fullName ?? viewer.name, required: true }), field('Admission number', 'admission_no', { value: profile?.admissionNo, required: !profile, max: 50, attributes: profile ? 'disabled' : '' }))}
    ${columns(field('Course', 'course', { value: profile?.course }), field('Phone', 'phone', { value: profile?.phone, type: 'tel', max: 35 }))}${submit('Save profile')}</form>`)
    + (profile ? card(`<h2 class="h5">Change password</h2><p data-auth-notice role="status" hidden></p><form data-auth="change" method="post" action="/account/password">${columns(field('Current password', 'current_password', { type: 'password', required: true, attributes: 'autocomplete="current-password"' }), field('New password', 'password', { type: 'password', required: true, attributes: 'autocomplete="new-password" minlength="8"' }))}${submit('Update password')}</form>`) : ''), viewer, 'Your school details identify reports submitted from your account.');
}

export function newReport(viewer, profile, trainerList, error, form = {}) {
  return page('Report a missing mark', card(`${error ? alert(error) : ''}<form action="/actions/submit_report.php" method="post">
    ${columns(field('Student name', 'student_name', { value: profile.fullName, attributes: 'disabled' }), field('Admission number', 'admission_no', { value: profile.admissionNo, attributes: 'disabled' }))}
    ${columns(field('Trainer name', 'trainer', { value: form.trainer, required: true, attributes: 'list="trainers"' }), select('Semester', 'semester', semesters, form.semester, 'Select a semester', true))}
    <datalist id="trainers">${trainerList.map(({ name }) => `<option value="${escape(name)}"></option>`).join('')}</datalist>
    ${columns(field('Unit name', 'unit_name', { value: form.unit_name, max: 180, required: true }), field('Unit code', 'unit_code', { value: form.unit_code, max: 40 }))}
    ${columns(field('Academic year', 'academic_year', { value: form.academic_year, max: 20, required: true, attributes: 'placeholder="2025/2026"' }), select('Missing assessment', 'assessment', assessments, form.assessment, 'Select an assessment', true))}
    ${area('Additional details', 'description', form.description)}${submit('Submit report')}</form>`), viewer, 'Identify the missing result. Staff record awards after verification.');
}

function badge(status) {
  const style = { Pending: 'pending', 'In Review': 'review', Resolved: 'resolved', Rejected: 'rejected' }[status];
  return `<span class="badge badge-status-${style}">${escape(status)}</span>`;
}

function reportLink(report, staffView) {
  return `<a class="fw-semibold text-success" href="${staffView ? '/admin/report_view.php' : '/report_details.php'}?id=${report.id}">${escape(report.reference)}</a>`;
}

function tracker(status) {
  const position = status === 'Pending' ? 0 : status === 'In Review' ? 1 : 2;
  const labels = ['Submitted', 'Under verification', status === 'Rejected' ? 'Rejected' : 'Resolved'];
  return `<div class="kt-tracker mb-4" aria-label="Case progress">${labels.map((label, index) => `${index ? `<span class="kt-tracker-line ${index <= position ? 'done' : ''}"></span>` : ''}<div class="kt-tracker-step kt-tracker-${index < position ? 'done' : index > position ? 'pending' : status === 'Rejected' ? 'rejected' : 'current'}" ${index === position ? 'aria-current="step"' : ''}><span class="kt-tracker-dot">${index + 1}</span><span class="kt-tracker-label">${label}</span></div>`).join('')}</div>`;
}

function reportTable(rows, staffView, bulk = false) {
  if (!rows.length) return `<p class="kt-empty mb-0">${icon('inbox')}<br>No reports found.</p>`;
  return `<div class="table-responsive"><table class="table table-kt align-middle mb-0"><thead><tr>${bulk ? '<th scope="col">Select</th>' : ''}<th scope="col">Reference</th>${staffView ? '<th scope="col">Student</th>' : ''}<th scope="col">Unit / trainer</th><th scope="col">Assessment</th><th scope="col">Status</th><th scope="col">Submitted</th></tr></thead><tbody>${rows.map((report) => `<tr>${bulk ? `<td><input type="checkbox" name="report_id" value="${report.id}" form="bulk" class="form-check-input" aria-label="Select ${escape(report.reference)}"></td>` : ''}<td>${reportLink(report, staffView)}</td>${staffView ? `<td>${escape(report.studentName)}<br><small>${escape(report.admissionNo)}</small></td>` : ''}<td>${escape(report.unitName)}<br><small>${escape(report.trainer)}</small></td><td>${escape(report.assessment)}</td><td>${badge(report.status)}</td><td>${date(report.createdAt)}</td></tr>`).join('')}</tbody></table></div>`;
}

export function pagination(path, query, number, more) {
  const link = (target) => `${path}?${new URLSearchParams({ ...query, page: String(target) })}`;
  return `<nav class="d-flex gap-3 align-items-center mt-3" aria-label="Pagination">${number > 1 ? button('Previous', link(number - 1), 'soft') : ''}<span class="kt-muted small">Page ${number}</span>${more ? button('Next', link(number + 1), 'soft') : ''}</nav>`;
}

export function reportsPage(viewer, list, query, staffView, error) {
  const path = staffView ? '/admin/reports.php' : '/my_reports.php';
  const bulk = staffView && list.rows.length;
  return page(staffView ? 'Manage reports' : 'My reports', `${staffView ? '' : `<div class="mb-3">${button('New report', '/report_new.php')}</div>`}
    ${card(`<form method="get" action="${path}">${columns(field('Search reports', 'q', { value: query.q, max: 100 }), select('Status', 'status', statuses, query.status, 'All statuses'), select('Semester', 'semester', semesters, query.semester, 'All semesters'))}${submit('Filter reports')}</form>`)}
    ${error ? alert(error) : ''}${card(`${bulk ? '<form id="bulk" method="post" action="/admin/actions/bulk_update.php" class="d-flex flex-wrap gap-2 mb-3"><button name="status" value="In Review" class="btn btn-kt-soft">Mark selected in review</button><button name="status" value="Resolved" class="btn btn-kt-soft">Resolve selected</button><button name="status" value="Rejected" class="btn btn-outline-danger">Reject selected</button></form>' : ''}${reportTable(list.rows, staffView, bulk)}${pagination(path, query, list.page, list.more)}`)}`, viewer);
}

export function dashboard(viewer, data, staffView) {
  const { totals, recent, notices, breakdown, leadingTrainers } = data;
  const states = ['All cases', ...statuses];
  const cards = states.map((status, index) => {
    const total = status === 'All cases' ? Object.values(totals).reduce((sum, count) => sum + count, 0) : totals[status] ?? 0;
    return `<div class="col"><div class="kt-stat p-3 bg-${Math.min(index + 1, 4)}"><div class="num">${total}</div><div class="small">${status}</div></div></div>`;
  }).join('');
  const details = staffView
    ? `<div class="row g-4"><div class="col-md-6">${card(`<h2 class="h5">Reports by semester</h2>${breakdown.length ? breakdown.map((entry) => `<p class="mb-2">${escape(entry.semester)} <strong class="float-end">${entry.total}</strong></p>`).join('') : paragraph('No reports yet.')}`)}</div><div class="col-md-6">${card(`<h2 class="h5">Trainers with most cases</h2>${leadingTrainers.length ? leadingTrainers.map((entry) => `<p class="mb-2">${escape(entry.trainer)} <strong class="float-end">${entry.total}</strong></p>`).join('') : paragraph('No reports yet.')}`)}</div></div>`
    : `${notices.length ? card(`<h2 class="h5">Notifications</h2>${notices.map((notice) => `<article class="border-top pt-2 mt-2"><a href="/report_details.php?id=${notice.reportId}" class="fw-semibold text-success">${escape(notice.title)}</a>${notice.read ? '' : ' <span class="badge text-bg-warning">New</span>'}<div class="small kt-muted">${escape(notice.message)} · ${date(notice.createdAt)}</div></article>`).join('')}<form class="mt-3" method="post" action="/actions/mark_notifications_read.php">${submit('Mark all as read')}</form>`) : ''}${recent.filter((report) => statuses.slice(0, 2).includes(report.status)).slice(0, 3).map((report) => card(`<h2 class="h6 mb-3">${reportLink(report, false)} · ${escape(report.unitName)}</h2>${tracker(report.status)}`)).join('')}`;
  return page(staffView ? 'Staff dashboard' : 'Student dashboard', `<div class="row row-cols-2 row-cols-lg-5 g-3 mb-4">${cards}</div>${details}${card(`<div class="d-flex align-items-center justify-content-between gap-2 mb-3"><h2 class="h5 mb-0">Recent reports</h2>${button(staffView ? 'Manage reports' : 'New report', staffView ? '/admin/reports.php' : '/report_new.php', 'soft')}</div>${reportTable(recent, staffView)}`)}`, viewer, staffView ? 'Review, verify and resolve missing marks cases.' : `Welcome, ${data.profile.fullName}.`);
}

export function casePage(viewer, report, changes, staffView, success, error) {
  const award = openAwards(report.assessment);
  const mark = (value, maximum) => value === null ? 'Pending award' : `${Number(value)} / ${maximum}`;
  const details = [['Student', report.studentName], ['Admission number', report.admissionNo], ['Trainer', report.trainer], ['Unit', [report.unitName, report.unitCode].filter(Boolean).join(' · ')], ['Semester', report.semester], ['Academic year', report.academicYear], ['Assessment', report.assessment], ['Submitted', date(report.createdAt)]];
  if (award.cat1) details.push(['CAT 1', mark(report.cat1, 30)]);
  if (award.cat2) details.push(['CAT 2', mark(report.cat2, 70)]);
  if (award.examGrade) details.push(['End Term', report.examGrade ?? 'Pending award']);
  const controls = staffView ? card(`<h2 class="h5">Review and award marks</h2><form method="post" action="/admin/actions/update_report.php?id=${report.id}">${error ? alert(error) : ''}
    ${select('Status', 'status', statuses, report.status)}${area('Staff remarks', 'remarks', report.remarks)}
    ${columns(...[award.cat1 ? field('CAT 1 / 30', 'cat1', { type: 'number', value: report.cat1, attributes: 'min="0" max="30" step="0.01"' }) : '', award.cat2 ? field('CAT 2 / 70', 'cat2', { type: 'number', value: report.cat2, attributes: 'min="0" max="70" step="0.01"' }) : '', award.examGrade ? select('End Term grade', 'exam_grade', grades, report.examGrade, 'Select a grade') : ''].filter(Boolean))}
    <div class="d-flex flex-wrap gap-2"><button name="intent" value="award" class="btn btn-kt-primary">Save awards and status</button><button name="intent" value="status" class="btn btn-kt-soft">Update status only</button></div></form>`) : '';
  return page(success ? 'Report submitted successfully' : 'Case details', `${success ? alert(`Your case reference is ${report.reference}.`, 'success') : ''}${card(`<div class="d-flex flex-wrap gap-3 align-items-center justify-content-between mb-3"><h2 class="h4 mb-0">${escape(report.reference)}</h2>${badge(report.status)}</div>${tracker(report.status)}
    <dl class="row g-2">${details.map(([label, value]) => `<div class="col-sm-6 col-lg-4"><dt class="small kt-muted">${label}</dt><dd>${escape(value)}</dd></div>`).join('')}</dl>${report.description ? `<h3 class="h6">Student notes</h3>${paragraph(report.description)}` : ''}${report.remarks ? `<h3 class="h6">Staff remarks</h3>${paragraph(report.remarks)}` : ''}`)}${controls}${card(`<h2 class="h5 mb-3">Status history</h2><ol class="kt-timeline list-unstyled mb-0">${changes.map((change) => `<li class="kt-timeline-item"><span class="kt-timeline-dot"></span>${badge(change.status)} <span class="small kt-muted">${date(change.createdAt)}</span><p class="mb-0 mt-2">${escape(change.remarks)}</p></li>`).join('')}</ol>`)}${button('Back to reports', staffView ? '/admin/reports.php' : '/my_reports.php', 'soft')}`, viewer);
}

export function directory(viewer, kind, list, query = {}, number = 1, more = false, error = '') {
  const canManage = viewer.role === 'admin';
  const trainerForm = `<form method="post" class="mb-4">${field('Trainer name', 'name', { required: true })}${submit('Add trainer')}</form>`;
  const staffForm = canManage ? `<form method="post" class="mb-4">${columns(field('Full name', 'full_name', { required: true }), field('Staff number', 'staff_no', { required: true, max: 50 }), field('Email', 'email', { type: 'email', max: 254, required: true }))}${columns(select('Staff role', 'role', staffRoles, '', 'Select a role', true), field('Department', 'department'))}${submit('Grant staff access')}</form>` : '';
  const studentForm = `<form method="get" class="mb-4">${field('Search by name or admission number', 'q', { value: query.q, max: 100 })}${submit('Search')}</form>`;
  const sets = {
    Trainers: [trainerForm, ['Trainer'], ({ name }) => `<td>${escape(name)}</td><td><form method="post"><input type="hidden" name="name" value="${escape(name)}"><button name="intent" value="remove" class="btn btn-sm btn-outline-danger">Remove</button></form></td>`],
    Staff: [staffForm, ['Name', 'Staff no.', 'Email', 'Role', 'Department', 'Status'], (entry) => `<td>${escape(entry.fullName)}</td><td>${escape(entry.staffNo)}</td><td>${escape(entry.email)}</td><td>${escape(entry.role)}</td><td>${escape(entry.department)}</td><td>${entry.active ? 'Active' : 'Disabled'}</td>${canManage ? `<td><form method="post"><input type="hidden" name="email" value="${escape(entry.email)}"><button name="intent" value="toggle" class="btn btn-sm btn-kt-soft">${entry.active ? 'Disable' : 'Enable'}</button></form></td>` : ''}`],
    Students: [studentForm, ['Name', 'Admission no.', 'Course', 'Email'], (entry) => `<td>${escape(entry.fullName)}</td><td>${escape(entry.admissionNo)}</td><td>${escape(entry.course)}</td><td>${escape(entry.email)}</td>`],
  };
  const [form, headers, row] = sets[kind];
  const actions = kind === 'Trainers' || (kind === 'Staff' && canManage);
  return page(`${kind} directory`, card(`${error ? alert(error) : ''}${kind === 'Staff' && canManage ? paragraph('Add a staff email here, then have that person create and verify an account with the same address.') : ''}${form}${list.length ? `<div class="table-responsive"><table class="table table-kt"><thead><tr>${headers.map((title) => `<th scope="col">${title}</th>`).join('')}${actions ? '<th scope="col">Action</th>' : ''}</tr></thead><tbody>${list.map((entry) => `<tr>${row(entry)}</tr>`).join('')}</tbody></table></div>` : paragraph('No records found.')}${kind === 'Students' ? pagination('/admin/students.php', query, number, more) : ''}`), viewer);
}
