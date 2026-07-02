DELETE FROM module_permissions WHERE module_id IN (114,115,116,117,118) AND role_id NOT IN (1,21,35,36);

INSERT IGNORE INTO module_permissions (role_id, module_id, can_view, can_create, can_edit, can_delete, can_approve, can_export) VALUES
(35, 114, 1, 1, 0, 0, 0, 0),
(36, 115, 1, 1, 0, 0, 0, 0),
(21, 116, 1, 1, 1, 1, 0, 1),
(21, 117, 1, 0, 1, 0, 1, 0),
(1, 118, 1, 0, 0, 0, 1, 0);
