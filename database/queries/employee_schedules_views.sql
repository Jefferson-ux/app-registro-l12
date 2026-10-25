CREATE OR REPLACE VIEW v_employee_schedules AS
SELECT
    e.employee_code,
    CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
	 CASE
	 	WHEN wsd.day_of_week = 1 THEN 'LUNES'
	 	WHEN wsd.day_of_week = 2 THEN 'MARTES'
	 	WHEN wsd.day_of_week = 3 THEN 'MIERCOLES'
	 	WHEN wsd.day_of_week = 4 THEN 'JUEVES'
	 	WHEN wsd.day_of_week = 5 THEN 'VIERNES'
	 	WHEN wsd.day_of_week = 6 THEN 'SABADO'
	 	WHEN wsd.day_of_week = 7 THEN 'DOMINGO'
	 	ELSE 'INDEFINIDO'
	 END AS day_of_week,
    wsd.check_in_time,
    wsd.check_out_time,
    wsd.break_start_time AS break_start,
    wsd.break_end_time AS break_end,
    e.tenant_id,
    e.branch_id,
    e.department_id,
    ws.name AS work_schedule_name
FROM employees e
JOIN employee_schedules es
    ON es.employee_id = e.id
    AND es.status = 1
    AND es.start_date <= CURDATE()
    AND (es.end_date IS NULL OR es.end_date >= CURDATE())
JOIN work_schedules ws
    ON ws.id = es.work_schedule_id
    AND ws.deleted_at IS NULL
JOIN work_schedule_days wsd
    ON wsd.work_schedule_id = ws.id
WHERE e.employment_status = 'active'
    AND e.deleted_at IS NULL;