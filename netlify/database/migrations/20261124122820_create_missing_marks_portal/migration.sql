CREATE TYPE "assessment_type" AS ENUM('CAT 1', 'CAT 2', 'END TERM', 'Both CATS & End Term');--> statement-breakpoint
CREATE TYPE "exam_grade" AS ENUM('A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D+', 'D', 'D-', 'E');--> statement-breakpoint
CREATE TYPE "semester" AS ENUM('Semester 1', 'Semester 2', 'Semester 3');--> statement-breakpoint
CREATE TYPE "staff_role" AS ENUM('Registrar', 'HOD', 'Trainer');--> statement-breakpoint
CREATE TYPE "report_status" AS ENUM('Pending', 'In Review', 'Resolved', 'Rejected');--> statement-breakpoint
CREATE TABLE "report_history" (
	"id" integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY (sequence name "report_history_id_seq" INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START WITH 1 CACHE 1),
	"report_id" integer NOT NULL,
	"status" "report_status" NOT NULL,
	"remarks" text NOT NULL,
	"actor_id" uuid NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "notifications" (
	"id" integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY (sequence name "notifications_id_seq" INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START WITH 1 CACHE 1),
	"student_id" uuid NOT NULL,
	"report_id" integer NOT NULL,
	"title" text NOT NULL,
	"message" text NOT NULL,
	"read" boolean DEFAULT false NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "reports" (
	"id" integer PRIMARY KEY GENERATED ALWAYS AS IDENTITY (sequence name "reports_id_seq" INCREMENT BY 1 MINVALUE 1 MAXVALUE 2147483647 START WITH 1 CACHE 1),
	"reference" text NOT NULL UNIQUE,
	"student_id" uuid NOT NULL,
	"student_name" text NOT NULL,
	"admission_no" text NOT NULL,
	"trainer" text NOT NULL,
	"unit_name" text NOT NULL,
	"unit_code" text DEFAULT '' NOT NULL,
	"semester" "semester" NOT NULL,
	"academic_year" text NOT NULL,
	"assessment" "assessment_type" NOT NULL,
	"description" text DEFAULT '' NOT NULL,
	"status" "report_status" DEFAULT 'Pending'::"report_status" NOT NULL,
	"cat1" numeric(4,2),
	"cat2" numeric(4,2),
	"exam_grade" "exam_grade",
	"remarks" text DEFAULT '' NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL,
	"updated_at" timestamp with time zone DEFAULT now() NOT NULL,
	CONSTRAINT "valid_cat1" CHECK ("cat1" between 0 and 30),
	CONSTRAINT "valid_cat2" CHECK ("cat2" between 0 and 70)
);
--> statement-breakpoint
CREATE TABLE "staff_access" (
	"email" text PRIMARY KEY,
	"full_name" text NOT NULL,
	"staff_no" text NOT NULL UNIQUE,
	"role" "staff_role" NOT NULL,
	"department" text DEFAULT '' NOT NULL,
	"active" boolean DEFAULT true NOT NULL
);
--> statement-breakpoint
CREATE TABLE "students" (
	"id" uuid PRIMARY KEY,
	"email" text NOT NULL UNIQUE,
	"full_name" text NOT NULL,
	"admission_no" text NOT NULL UNIQUE,
	"phone" text DEFAULT '' NOT NULL,
	"course" text DEFAULT '' NOT NULL,
	"created_at" timestamp with time zone DEFAULT now() NOT NULL
);
--> statement-breakpoint
CREATE TABLE "trainers" (
	"name" text PRIMARY KEY
);
--> statement-breakpoint
CREATE INDEX "history_report" ON "report_history" ("report_id","id");--> statement-breakpoint
CREATE INDEX "notifications_student_recent" ON "notifications" ("student_id","id");--> statement-breakpoint
CREATE INDEX "reports_student_recent" ON "reports" ("student_id","id");--> statement-breakpoint
CREATE INDEX "reports_status_recent" ON "reports" ("status","id");--> statement-breakpoint
ALTER TABLE "report_history" ADD CONSTRAINT "report_history_report_id_reports_id_fkey" FOREIGN KEY ("report_id") REFERENCES "reports"("id");--> statement-breakpoint
ALTER TABLE "notifications" ADD CONSTRAINT "notifications_student_id_students_id_fkey" FOREIGN KEY ("student_id") REFERENCES "students"("id");--> statement-breakpoint
ALTER TABLE "notifications" ADD CONSTRAINT "notifications_report_id_reports_id_fkey" FOREIGN KEY ("report_id") REFERENCES "reports"("id");--> statement-breakpoint
ALTER TABLE "reports" ADD CONSTRAINT "reports_student_id_students_id_fkey" FOREIGN KEY ("student_id") REFERENCES "students"("id");