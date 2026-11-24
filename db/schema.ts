import { sql } from 'drizzle-orm';
import { boolean, check, index, integer, numeric, pgEnum, pgTable, text, timestamp, uuid } from 'drizzle-orm/pg-core';

export const statuses = ['Pending', 'In Review', 'Resolved', 'Rejected'] as const;
export const assessments = ['CAT 1', 'CAT 2', 'END TERM', 'Both CATS & End Term'] as const;
export const semesters = ['Semester 1', 'Semester 2', 'Semester 3'] as const;
export const grades = ['A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'D-', 'E'] as const;
export const staffRoles = ['Registrar', 'HOD', 'Trainer'] as const;

export const status = pgEnum('report_status', statuses);
export const assessment = pgEnum('assessment_type', assessments);
export const semester = pgEnum('semester', semesters);
export const grade = pgEnum('exam_grade', grades);
export const staffRole = pgEnum('staff_role', staffRoles);
const time = (name: string) => timestamp(name, { withTimezone: true }).defaultNow().notNull();

export const students = pgTable('students', {
  id: uuid().primaryKey(),
  email: text().notNull().unique(),
  fullName: text('full_name').notNull(),
  admissionNo: text('admission_no').notNull().unique(),
  phone: text().notNull().default(''),
  course: text().notNull().default(''),
  createdAt: time('created_at'),
});

export const staff = pgTable('staff_access', {
  email: text().primaryKey(),
  fullName: text('full_name').notNull(),
  staffNo: text('staff_no').notNull().unique(),
  role: staffRole().notNull(),
  department: text().notNull().default(''),
  active: boolean().default(true).notNull(),
});

export const trainers = pgTable('trainers', {
  name: text().primaryKey(),
});

export const reports = pgTable('reports', {
  id: integer().primaryKey().generatedAlwaysAsIdentity(),
  reference: text().notNull().unique(),
  studentId: uuid('student_id').notNull().references(() => students.id),
  studentName: text('student_name').notNull(),
  admissionNo: text('admission_no').notNull(),
  trainer: text().notNull(),
  unitName: text('unit_name').notNull(),
  unitCode: text('unit_code').notNull().default(''),
  semester: semester().notNull(),
  academicYear: text('academic_year').notNull(),
  assessment: assessment().notNull(),
  description: text().notNull().default(''),
  status: status().notNull().default('Pending'),
  cat1: numeric({ precision: 4, scale: 2 }),
  cat2: numeric({ precision: 4, scale: 2 }),
  examGrade: grade('exam_grade'),
  remarks: text().notNull().default(''),
  createdAt: time('created_at'),
  updatedAt: time('updated_at'),
}, (table) => [
  index('reports_student_recent').on(table.studentId, table.id),
  index('reports_status_recent').on(table.status, table.id),
  check('valid_cat1', sql`${table.cat1} between 0 and 30`),
  check('valid_cat2', sql`${table.cat2} between 0 and 70`),
]);

export const history = pgTable('report_history', {
  id: integer().primaryKey().generatedAlwaysAsIdentity(),
  reportId: integer('report_id').notNull().references(() => reports.id),
  status: status().notNull(),
  remarks: text().notNull(),
  actorId: uuid('actor_id').notNull(),
  createdAt: time('created_at'),
}, (table) => [index('history_report').on(table.reportId, table.id)]);

export const notifications = pgTable('notifications', {
  id: integer().primaryKey().generatedAlwaysAsIdentity(),
  studentId: uuid('student_id').notNull().references(() => students.id),
  reportId: integer('report_id').notNull().references(() => reports.id),
  title: text().notNull(),
  message: text().notNull(),
  read: boolean().default(false).notNull(),
  createdAt: time('created_at'),
}, (table) => [index('notifications_student_recent').on(table.studentId, table.id)]);
