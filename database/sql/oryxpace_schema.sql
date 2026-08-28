-- Oryxpace-owned schema for the shared Crew Center database.
--
-- Review this file before running it. It intentionally does not create,
-- alter, or drop shared Crew Center tables such as pilots.
-- It also does not create a Laravel migrations table.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS oryxpace_departments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY departments_slug_unique (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oryxpace_projects (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    department_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    start_date DATE NULL,
    completion_date DATE NULL,
    is_archived TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY projects_department_id_index (department_id),
    CONSTRAINT projects_department_id_foreign
        FOREIGN KEY (department_id) REFERENCES oryxpace_departments (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oryxpace_kanban_columns (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    `order` INT NOT NULL DEFAULT 0,
    is_default TINYINT(1) NOT NULL DEFAULT 0,
    required_permission VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY kanban_columns_project_id_index (project_id),
    CONSTRAINT kanban_columns_project_id_foreign
        FOREIGN KEY (project_id) REFERENCES oryxpace_projects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oryxpace_project_tags (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    color VARCHAR(255) NOT NULL DEFAULT '#3B82F6',
    permission VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY project_tags_project_id_index (project_id),
    CONSTRAINT project_tags_project_id_foreign
        FOREIGN KEY (project_id) REFERENCES oryxpace_projects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oryxpace_tasks (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED NULL,
    kanban_column_id BIGINT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    resolution_user_id BIGINT UNSIGNED NULL,
    created_by_pilot_id BIGINT UNSIGNED NULL,
    due_date DATE NULL,
    order_in_column INT NOT NULL DEFAULT 0,
    is_template TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY tasks_project_id_index (project_id),
    KEY tasks_parent_id_index (parent_id),
    KEY tasks_kanban_column_id_index (kanban_column_id),
    CONSTRAINT tasks_project_id_foreign
        FOREIGN KEY (project_id) REFERENCES oryxpace_projects (id) ON DELETE CASCADE,
    CONSTRAINT tasks_parent_id_foreign
        FOREIGN KEY (parent_id) REFERENCES oryxpace_tasks (id) ON DELETE CASCADE,
    CONSTRAINT tasks_kanban_column_id_foreign
        FOREIGN KEY (kanban_column_id) REFERENCES oryxpace_kanban_columns (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oryxpace_task_assignees (
    task_id BIGINT UNSIGNED NOT NULL,
    pilot_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (task_id, pilot_id),
    CONSTRAINT task_assignees_task_id_foreign
        FOREIGN KEY (task_id) REFERENCES oryxpace_tasks (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oryxpace_task_tags (
    task_id BIGINT UNSIGNED NOT NULL,
    project_tag_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (task_id, project_tag_id),
    CONSTRAINT task_tags_task_id_foreign
        FOREIGN KEY (task_id) REFERENCES oryxpace_tasks (id) ON DELETE CASCADE,
    CONSTRAINT task_tags_project_tag_id_foreign
        FOREIGN KEY (project_tag_id) REFERENCES oryxpace_project_tags (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oryxpace_task_activity_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    task_id BIGINT UNSIGNED NOT NULL,
    pilot_id BIGINT UNSIGNED NULL,
    activity_type VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY task_activity_logs_task_id_index (task_id),
    CONSTRAINT task_activity_logs_task_id_foreign
        FOREIGN KEY (task_id) REFERENCES oryxpace_tasks (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oryxpace_custom_fields (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    project_id BIGINT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    field_type VARCHAR(255) NOT NULL DEFAULT 'text',
    options JSON NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY custom_fields_project_id_index (project_id),
    CONSTRAINT custom_fields_project_id_foreign
        FOREIGN KEY (project_id) REFERENCES oryxpace_projects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oryxpace_task_custom_field_values (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    task_id BIGINT UNSIGNED NOT NULL,
    custom_field_id BIGINT UNSIGNED NOT NULL,
    value TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY task_custom_field_values_task_field_unique (task_id, custom_field_id),
    CONSTRAINT task_custom_field_values_task_id_foreign
        FOREIGN KEY (task_id) REFERENCES oryxpace_tasks (id) ON DELETE CASCADE,
    CONSTRAINT task_custom_field_values_custom_field_id_foreign
        FOREIGN KEY (custom_field_id) REFERENCES oryxpace_custom_fields (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oryxpace_task_schedules (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    project_id BIGINT UNSIGNED NOT NULL,
    cron_expression VARCHAR(255) NOT NULL DEFAULT '0 0 * * *',
    subtask_templates JSON NULL,
    custom_fields_template JSON NULL,
    last_run_at TIMESTAMP NULL DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY task_schedules_project_id_index (project_id),
    CONSTRAINT task_schedules_project_id_foreign
        FOREIGN KEY (project_id) REFERENCES oryxpace_projects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oryxpace_project_subscriptions (
    project_id BIGINT UNSIGNED NOT NULL,
    pilot_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (project_id, pilot_id),
    CONSTRAINT project_subscriptions_project_id_foreign
        FOREIGN KEY (project_id) REFERENCES oryxpace_projects (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oryxpace_user_notifications_settings (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    pilot_id BIGINT UNSIGNED NOT NULL,
    channel VARCHAR(255) NOT NULL DEFAULT 'discord',
    event_type VARCHAR(255) NOT NULL DEFAULT 'all',
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY user_notifications_settings_pilot_channel_event_unique
        (pilot_id, channel, event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oryxpace_api_keys (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    `key` VARCHAR(64) NOT NULL,
    abilities JSON NULL,
    last_used_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY api_keys_key_unique (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oryxpace_custom_permissions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY custom_permissions_slug_unique (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oryxpace_pilot_permissions (
    pilot_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (pilot_id, permission_id),
    CONSTRAINT pilot_permissions_permission_id_foreign
        FOREIGN KEY (permission_id) REFERENCES oryxpace_custom_permissions (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
