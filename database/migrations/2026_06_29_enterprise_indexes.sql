-- ===============================================================
-- Enterprise Performance Migration: Missing Indexes & Optimizations
-- Compatible with MySQL 5.x+
-- Uses robust stored procedure that handles missing tables/columns
-- Run: mysql -u root -p < this_file.sql
-- ===============================================================

-- Helper procedure: creates index only if table + columns + index all check out
DROP PROCEDURE IF EXISTS `add_index_if_missing`;
DELIMITER $$
CREATE PROCEDURE `add_index_if_missing`(
    p_schema VARCHAR(200),
    p_table VARCHAR(200),
    p_index VARCHAR(200),
    p_columns TEXT
)
BEGIN
    DECLARE tbl_exists INT;
    DECLARE idx_exists INT;
    DECLARE cols_ok INT;
    DECLARE col_name VARCHAR(200);
    DECLARE pos INT;
    DECLARE cols_text TEXT;
    DECLARE col_list TEXT;

    -- Check table exists
    SET tbl_exists = (SELECT COUNT(*) FROM information_schema.TABLES
                      WHERE TABLE_SCHEMA = p_schema AND TABLE_NAME = p_table);
    IF tbl_exists = 0 THEN
        SELECT CONCAT('SKIP: Table `', p_schema, '`.`', p_table, '` not found') AS msg;
    ELSE
        -- Check all columns referenced exist
        -- Parse comma-separated column names (remove backticks and whitespace)
        SET cols_text = REPLACE(p_columns, '`', '');
        SET cols_text = REPLACE(cols_text, ' ', '');
        SET cols_ok = 1;
        SET col_list = '';

        WHILE LENGTH(cols_text) > 0 DO
            SET pos = LOCATE(',', cols_text);
            IF pos = 0 THEN
                SET col_name = cols_text;
                SET cols_text = '';
            ELSE
                SET col_name = SUBSTRING(cols_text, 1, pos - 1);
                SET cols_text = SUBSTRING(cols_text, pos + 1);
            END IF;

            SET col_list = CONCAT(col_list, ',', col_name);
            SET cols_ok = LEAST(cols_ok, (SELECT COUNT(*) FROM information_schema.COLUMNS
                                          WHERE TABLE_SCHEMA = p_schema AND TABLE_NAME = p_table
                                          AND COLUMN_NAME = col_name));
        END WHILE;

        IF cols_ok = 0 THEN
            SELECT CONCAT('SKIP: Column(s) [', TRIM(LEADING ',' FROM col_list), '] not found in `', p_schema, '`.`', p_table, '`') AS msg;
        ELSE
            -- Check index doesn't already exist
            SET idx_exists = (SELECT COUNT(*) FROM information_schema.STATISTICS
                              WHERE TABLE_SCHEMA = p_schema AND TABLE_NAME = p_table AND INDEX_NAME = p_index);
            IF idx_exists = 0 THEN
                SET @sql = CONCAT('ALTER TABLE `', p_schema, '`.`', p_table, '` ADD INDEX `', p_index, '` (', p_columns, ')');
                PREPARE stmt FROM @sql;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
                SELECT CONCAT('OK: Created index `', p_index, '` on `', p_schema, '`.`', p_table, '`') AS msg;
            ELSE
                SELECT CONCAT('SKIP: Index `', p_index, '` already exists on `', p_schema, '`.`', p_table, '`') AS msg;
            END IF;
        END IF;
    END IF;
END$$
DELIMITER ;

SELECT '=== Staffs DB Indexes ===' AS '';
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'staff', 'idx_staff_email', '`email`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'staff', 'idx_staff_status', '`status`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'staff', 'idx_staff_role_status', '`role_id`,`status`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'staff_profiles', 'idx_sp_department', '`department`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'staff_profiles', 'idx_sp_phone', '`phone`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'staff_attendance', 'idx_sa_staff_date', '`staff_id`,`date`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'staff_attendance', 'idx_sa_status_date', '`status`,`date`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'staff_leave_requests', 'idx_slr_dates', '`start_date`,`end_date`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'staff_leave_requests', 'idx_slr_type', '`leave_type_id`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'notifications', 'idx_notif_user_read', '`user_id`,`is_read`,`created_at`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'notifications', 'idx_notif_type', '`notification_type`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'notifications', 'idx_notif_created', '`created_at`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'activity_log', 'idx_al_user_date', '`user_id`,`created_at`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'activity_log', 'idx_al_action', '`action`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'activity_log', 'idx_al_module', '`module`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'activity_logs', 'idx_als_user_date', '`user_id`,`created_at`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'payroll_items', 'idx_pi_status', '`status`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'payroll_records', 'idx_pr_staff_year', '`staff_id`,`year`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'payroll_payments', 'idx_pp_staff_date', '`staff_id`,`payment_date`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'payroll_loans', 'idx_pl_status_staff', '`status`,`staff_id`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'payroll_approvals', 'idx_pa_approver', '`approved_by`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'students', 'idx_stu_email', '`email`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'students', 'idx_stu_status', '`status`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'students', 'idx_stu_program', '`program_id`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'students', 'idx_stu_fullname', '`full_name`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'student_profiles', 'idx_stuprof_phone', '`phone`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'student_profiles', 'idx_stuprof_nsin', '`nsin_number`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'student_fee_accounts', 'idx_sfa_status_student', '`status`,`student_id`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'student_fee_accounts', 'idx_sfa_invoice', '`invoice_number`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'student_invoices', 'idx_sinv_student_date', '`student_id`,`created_at`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'student_invoices', 'idx_sinv_due', '`due_date`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'student_messages', 'idx_smsg_recipient', '`recipient_id`,`is_read`,`created_at`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'student_messages', 'idx_smsg_sender', '`sender_id`,`created_at`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'student_requests', 'idx_sreq_status_assigned', '`status`,`assigned_to`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'student_requests', 'idx_sreq_type', '`request_type`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'student_discipline', 'idx_sd_status_date', '`status`,`created_at`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'student_discipline', 'idx_sd_reporter', '`reported_by`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'student_sick_leave', 'idx_ssl_date_range', '`leave_from`,`leave_to`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'student_sick_leave', 'idx_ssl_program_status', '`program`,`status`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'daily_sick_records', 'idx_dsr_date_severity', '`visit_date`,`severity`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'daily_sick_records', 'idx_dsr_status_date', '`status`,`visit_date`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'medicine_stock', 'idx_ms_category_status', '`category`,`status`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'medicine_stock', 'idx_ms_supplier', '`supplier`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'medicine_stock_transactions', 'idx_mst_type_date', '`transaction_type`,`transaction_date`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'applicants', 'idx_app_status_date', '`status`,`created_at`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'applicants', 'idx_app_phone', '`phone`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'applicants', 'idx_app_email', '`email`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'admission_activity_logs', 'idx_aal_module_action', '`module`,`action`,`created_at`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'admission_activity_logs', 'idx_aal_user', '`user_id`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'exam_results', 'idx_er_exam_student', '`exam_id`,`student_id`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'exam_results', 'idx_er_grade', '`grade`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'examination_records', 'idx_er2_student_course', '`student_id`,`course_code`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'examination_records', 'idx_er2_type_status', '`exam_type`,`grade_status`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'academic_course_catalog', 'idx_acc_department_status', '`department`,`status`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'course_registrations', 'idx_cr_course_student', '`course_id`,`student_id`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'course_registrations', 'idx_cr_status_semester', '`status`,`semester`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'graduation_candidates', 'idx_gc_status_date', '`status`,`created_at`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'certificates', 'idx_cert_type_status', '`certificate_type`,`status`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'transcripts', 'idx_transcript_status', '`status`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'job_vacancies', 'idx_jv_status_date', '`status`,`posted_date`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'job_applications', 'idx_ja_vacancy_status', '`vacancy_id`,`status`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'job_applications', 'idx_ja_email', '`email`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'bursar_invoices', 'idx_bi_status_date', '`status`,`created_at`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'bursar_payments', 'idx_bp_date', '`payment_date`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'bursar_payments', 'idx_bp_method', '`payment_method`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'bursar_receipts', 'idx_br_student_date', '`student_id`,`created_at`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'bursar_general_ledger', 'idx_bgl_account_date', '`account_code`,`entry_date`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'bursar_cashbook', 'idx_bc_date_type', '`transaction_date`,`transaction_type`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'approval_requests', 'idx_ar_reference', '`reference_type`,`reference_id`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'approval_requests', 'idx_ar_requester_date', '`requester_id`,`created_at`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'approval_actions', 'idx_aa_approver', '`approver_id`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'approval_actions', 'idx_aa_date', '`created_at`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'store_requests', 'idx_sr_status_urgency', '`status`,`urgency`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'store_requests', 'idx_sr_approval', '`approval_request_id`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'inventory_transactions', 'idx_it_item_date', '`item_id`,`transaction_date`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'library_borrowing', 'idx_lb_member_status', '`member_id`,`status`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'library_borrowing', 'idx_lb_due', '`due_date`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'library_fines', 'idx_lf_paid', '`is_paid`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'compliance_requirements', 'idx_cr_category_status', '`category`,`status`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'institutional_alerts', 'idx_ia_dept_priority', '`department_code`,`priority`,`is_resolved`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'lab_equipment_maintenance', 'idx_lem_status_date', '`status`,`scheduled_date`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'departmental_budgets', 'idx_db_department_year', '`department_id`,`academic_year`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'expenditures', 'idx_exp_dept_date', '`department_id`,`created_at`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'expenditures', 'idx_exp_category', '`expense_category`');

CALL add_index_if_missing('igangaschoolofl_staffs_db', 'academic_audit_logs', 'idx_aal_entity', '`entity_type`,`entity_id`');
CALL add_index_if_missing('igangaschoolofl_staffs_db', 'system_logs', 'idx_sl_type_date', '`log_type`,`created_at`');

SELECT '=== Students DB Indexes ===' AS '';
CALL add_index_if_missing('igangaschoolofl_students_db', 'students', 'idx_stu_email', '`email`');
CALL add_index_if_missing('igangaschoolofl_students_db', 'students', 'idx_stu_status', '`status`');
CALL add_index_if_missing('igangaschoolofl_students_db', 'students', 'idx_stu_nsin', '`nsin_number`');

CALL add_index_if_missing('igangaschoolofl_students_db', 'student_profiles', 'idx_stuprof_phone', '`phone`');

CALL add_index_if_missing('igangaschoolofl_students_db', 'student_attendance', 'idx_sa_student_date', '`student_id`,`date`');

CALL add_index_if_missing('igangaschoolofl_students_db', 'student_fees', 'idx_sf_student_status', '`student_id`,`status`');

CALL add_index_if_missing('igangaschoolofl_students_db', 'payments', 'idx_pay_student_date', '`student_id`,`payment_date`');
CALL add_index_if_missing('igangaschoolofl_students_db', 'payments', 'idx_pay_receipt', '`receipt_number`');

CALL add_index_if_missing('igangaschoolofl_students_db', 'student_messages', 'idx_smsg_recipient', '`recipient_id`,`is_read`');

CALL add_index_if_missing('igangaschoolofl_students_db', 'student_invoices', 'idx_sinv_student_date', '`student_id`,`created_at`');

CALL add_index_if_missing('igangaschoolofl_students_db', 'fee_structures', 'idx_fs_program', '`program_id`');
CALL add_index_if_missing('igangaschoolofl_students_db', 'fee_structures', 'idx_fs_academic_year', '`academic_year`');

CALL add_index_if_missing('igangaschoolofl_students_db', 'budget_records', 'idx_br_department', '`department`');

CALL add_index_if_missing('igangaschoolofl_students_db', 'expenditure_records', 'idx_er_department_date', '`department`,`created_at`');

CALL add_index_if_missing('igangaschoolofl_students_db', 'clinical_placements', 'idx_cp_student', '`student_id`');
CALL add_index_if_missing('igangaschoolofl_students_db', 'clinical_placements_students', 'idx_cps_placement', '`placement_id`');

CALL add_index_if_missing('igangaschoolofl_students_db', 'lab_attendance', 'idx_la_session_student', '`session_id`,`student_id`');

CALL add_index_if_missing('igangaschoolofl_students_db', 'library_borrowing', 'idx_lb_member_status', '`member_id`,`status`');

SELECT '=== Website DB Indexes ===' AS '';
CALL add_index_if_missing('igangaschoolofl_website_db', 'news', 'idx_news_slug', '`slug`');
CALL add_index_if_missing('igangaschoolofl_website_db', 'news', 'idx_news_author', '`author_id`');
CALL add_index_if_missing('igangaschoolofl_website_db', 'news', 'idx_news_published', '`published_at`');

CALL add_index_if_missing('igangaschoolofl_website_db', 'contact_submissions', 'idx_cs_status_date', '`status`,`created_at`');

CALL add_index_if_missing('igangaschoolofl_website_db', 'student_applications', 'idx_sa_status_date', '`status`,`submitted_at`');
CALL add_index_if_missing('igangaschoolofl_website_db', 'student_applications', 'idx_sa_email', '`email`');

CALL add_index_if_missing('igangaschoolofl_website_db', 'portal_messages', 'idx_pm_recipient_read', '`recipient_id`,`is_read`');

CALL add_index_if_missing('igangaschoolofl_website_db', 'notification_reads', 'idx_nr_notification', '`notification_id`');

SELECT '=== ICT DB Indexes ===' AS '';
CALL add_index_if_missing('igangaschoolofl_ict', 'it_support_tickets', 'idx_ist_status_priority', '`status`,`priority`');
CALL add_index_if_missing('igangaschoolofl_ict', 'it_support_tickets', 'idx_ist_requester', '`requester_name`');
CALL add_index_if_missing('igangaschoolofl_ict', 'it_support_tickets', 'idx_ist_issue_type', '`issue_type`');

CALL add_index_if_missing('igangaschoolofl_ict', 'lab_bookings', 'idx_lb_date_status', '`booking_date`,`status`');
CALL add_index_if_missing('igangaschoolofl_ict', 'lab_bookings', 'idx_lb_instructor', '`instructor_name`');

CALL add_index_if_missing('igangaschoolofl_ict', 'lab_computers', 'idx_lc_location_status', '`location`,`status`');

CALL add_index_if_missing('igangaschoolofl_ict', 'network_devices', 'idx_nd_type_status', '`device_type`,`status`');

CALL add_index_if_missing('igangaschoolofl_ict', 'maintenance_logs', 'idx_ml_computer_status', '`computer_id`,`status`');
CALL add_index_if_missing('igangaschoolofl_ict', 'maintenance_logs', 'idx_ml_scheduled', '`scheduled_date`');

SELECT '=== Analyze Tables ===' AS '';
ANALYZE TABLE `igangaschoolofl_staffs_db`.`staff`;
ANALYZE TABLE `igangaschoolofl_staffs_db`.`students`;
ANALYZE TABLE `igangaschoolofl_staffs_db`.`notifications`;
ANALYZE TABLE `igangaschoolofl_staffs_db`.`activity_log`;
ANALYZE TABLE `igangaschoolofl_students_db`.`students`;
ANALYZE TABLE `igangaschoolofl_website_db`.`news`;
ANALYZE TABLE `igangaschoolofl_ict`.`it_support_tickets`;

-- Cleanup
DROP PROCEDURE IF EXISTS `add_index_if_missing`;
