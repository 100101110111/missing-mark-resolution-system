import { and, desc, eq, ilike, or } from 'drizzle-orm';
import { assessments, grades, history, notifications, reports, semesters, statuses } from '../../db/schema.ts';

export class InputError extends Error {}

export function input(form, name, maximum = 150, required = false) {
  const value = typeof form[name] === 'string' ? form[name].trim() : '';
  if ((required && !value) || value.length > maximum) {
    throw new InputError(`Enter a valid ${name.replaceAll('_', ' ')} (up to ${maximum} characters).`);
  }
  return value;
}

function choice(form, name, options) {
  const value = input(form, name, 150, true);
  if (!options.includes(value)) throw new InputError(`Select a valid ${name.replaceAll('_', ' ')}.`);
  return value;
}

export function reportFields(form) {
  return {
    trainer: input(form, 'trainer', 150, true),
    unitName: input(form, 'unit_name', 180, true),
    unitCode: input(form, 'unit_code', 40),
    academicYear: input(form, 'academic_year', 20, true),
    semester: choice(form, 'semester', semesters),
    assessment: choice(form, 'assessment', assessments),
    description: input(form, 'description', 4000),
  };
}

export function openAwards(assessment) {
  const combined = assessment === assessments[3];
  return {
    cat1: combined || assessment === assessments[0],
    cat2: combined || assessment === assessments[1],
    examGrade: combined || assessment === assessments[2],
  };
}

function mark(form, name, label, maximum) {
  const value = input(form, name, 12);
  if (!/^\d{1,2}(\.\d{1,2})?$/.test(value) || Number(value) > maximum) {
    throw new InputError(`${label} must be a number between 0 and ${maximum}, with at most two decimal places.`);
  }
  return Number(value).toFixed(2);
}

export function reviewFields(report, form) {
  const status = choice(form, 'status', statuses);
  const remarks = input(form, 'remarks', 4000);
  const award = openAwards(report.assessment);
  const changes = { status, remarks, cat1: report.cat1, cat2: report.cat2, examGrade: report.examGrade };
  if (form.intent === 'award') {
    if (award.cat1) changes.cat1 = mark(form, 'cat1', 'CAT 1', 30);
    if (award.cat2) changes.cat2 = mark(form, 'cat2', 'CAT 2', 70);
    if (award.examGrade) changes.examGrade = choice(form, 'exam_grade', grades);
  }
  if (status === 'Resolved' && ((award.cat1 && changes.cat1 === null) ||
    (award.cat2 && changes.cat2 === null) || (award.examGrade && changes.examGrade === null))) {
    throw new InputError('Save all required awards before resolving this case.');
  }
  return changes;
}

export async function saveReview(transaction, report, form, actorId) {
  const changes = reviewFields(report, form);
  const awardChanged = report.cat1 !== changes.cat1 || report.cat2 !== changes.cat2 || report.examGrade !== changes.examGrade;
  const statusChanged = report.status !== changes.status;
  if (!awardChanged && !statusChanged && report.remarks === changes.remarks) return;

  await transaction.update(reports).set({ ...changes, updatedAt: new Date() }).where(eq(reports.id, report.id));
  await transaction.insert(history).values({
    reportId: report.id, status: changes.status, actorId,
    remarks: [awardChanged ? 'Assessment awards updated.' : '', changes.remarks].filter(Boolean).join(' ') || 'Case updated.',
  });
  if (statusChanged) {
    await transaction.insert(notifications).values({
      studentId: report.studentId, reportId: report.id, title: `Case ${changes.status.toLowerCase()}`,
      message: [report.reference, changes.remarks].filter(Boolean).join(' — '),
    });
  }
}

export function reportFilters(query, studentId) {
  const filters = [];
  if (studentId) filters.push(eq(reports.studentId, studentId));
  if (statuses.includes(query.status)) filters.push(eq(reports.status, query.status));
  if (semesters.includes(query.semester)) filters.push(eq(reports.semester, query.semester));
  if (query.q) {
    const match = `%${query.q.slice(0, 100).replace(/[\\%_]/g, '\\$&')}%`;
    filters.push(or(ilike(reports.reference, match), ilike(reports.unitName, match), ilike(reports.trainer, match),
      ...(studentId ? [] : [ilike(reports.admissionNo, match), ilike(reports.studentName, match)])));
  }
  return and(...filters);
}

export async function listReports(db, query, studentId) {
  const page = Math.min(10000, Math.max(1, Number.parseInt(query.page, 10) || 1));
  const rows = await db.select().from(reports).where(reportFilters(query, studentId))
    .orderBy(desc(reports.id)).limit(16).offset((page - 1) * 15);
  return { page, rows: rows.slice(0, 15), more: rows.length > 15 };
}
