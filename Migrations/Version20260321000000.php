<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Mautic\IntegrationsBundle\Migration\AbstractMigration;

final class Version20260321000000 extends AbstractMigration
{
    protected function isApplicable(Schema $schema): bool
    {
        return !$schema->hasTable($this->concatPrefix('direct_push_device_tokens'));
    }

    protected function up(): void
    {
        $prefix = $this->prefix;

        $this->addSql("
            CREATE TABLE {$prefix}direct_push_device_tokens (
                id INT AUTO_INCREMENT NOT NULL,
                contact_id INT DEFAULT NULL,
                token VARCHAR(512) NOT NULL,
                platform VARCHAR(20) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                app_id VARCHAR(255) NOT NULL DEFAULT '',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                UNIQUE INDEX direct_push_contact_token_uniq (contact_id, token),
                INDEX direct_push_contact_active_idx (contact_id, is_active),
                INDEX direct_push_platform_active_idx (platform, is_active),
                CONSTRAINT FK_dp_device_token_contact FOREIGN KEY (contact_id) REFERENCES {$prefix}leads (id) ON DELETE SET NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ");

        $this->addSql("
            CREATE TABLE {$prefix}direct_push_notifications (
                id INT AUTO_INCREMENT NOT NULL,
                name VARCHAR(255) DEFAULT NULL,
                description LONGTEXT DEFAULT NULL,
                title VARCHAR(255) DEFAULT NULL,
                body LONGTEXT DEFAULT NULL,
                url VARCHAR(2048) DEFAULT NULL,
                image_url VARCHAR(2048) DEFAULT NULL,
                icon_url VARCHAR(2048) DEFAULT NULL,
                data_json JSON DEFAULT NULL,
                notification_type VARCHAR(25) NOT NULL DEFAULT 'template',
                category_id INT DEFAULT NULL,
                sent_count INT NOT NULL DEFAULT 0,
                is_published TINYINT(1) NOT NULL DEFAULT 0,
                date_added DATETIME DEFAULT NULL,
                date_modified DATETIME DEFAULT NULL,
                checked_out DATETIME DEFAULT NULL,
                checked_out_by INT DEFAULT NULL,
                checked_out_by_user VARCHAR(191) DEFAULT NULL,
                created_by INT DEFAULT NULL,
                created_by_user VARCHAR(191) DEFAULT NULL,
                modified_by INT DEFAULT NULL,
                modified_by_user VARCHAR(191) DEFAULT NULL,
                publish_up DATETIME DEFAULT NULL,
                publish_down DATETIME DEFAULT NULL,
                INDEX IDX_dp_notif_category (category_id),
                CONSTRAINT FK_dp_notif_category FOREIGN KEY (category_id) REFERENCES {$prefix}categories (id) ON DELETE SET NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ");

        $this->addSql("
            CREATE TABLE {$prefix}direct_push_notification_stats (
                id BIGINT AUTO_INCREMENT NOT NULL,
                notification_id INT DEFAULT NULL,
                contact_id INT DEFAULT NULL,
                device_token_id INT DEFAULT NULL,
                date_sent DATETIME DEFAULT NULL,
                is_failed TINYINT(1) NOT NULL DEFAULT 0,
                error_message VARCHAR(1024) DEFAULT NULL,
                is_clicked TINYINT(1) NOT NULL DEFAULT 0,
                date_clicked DATETIME DEFAULT NULL,
                retry_count INT NOT NULL DEFAULT 0,
                source VARCHAR(191) DEFAULT NULL,
                source_id INT DEFAULT NULL,
                tracking_hash VARCHAR(191) DEFAULT NULL,
                INDEX direct_push_stat_notif_sent_idx (notification_id, date_sent),
                INDEX direct_push_stat_contact_sent_idx (contact_id, date_sent),
                INDEX direct_push_stat_hash_idx (tracking_hash),
                CONSTRAINT FK_dp_stat_notification FOREIGN KEY (notification_id) REFERENCES {$prefix}direct_push_notifications (id) ON DELETE SET NULL,
                CONSTRAINT FK_dp_stat_contact FOREIGN KEY (contact_id) REFERENCES {$prefix}leads (id) ON DELETE SET NULL,
                CONSTRAINT FK_dp_stat_device_token FOREIGN KEY (device_token_id) REFERENCES {$prefix}direct_push_device_tokens (id) ON DELETE SET NULL,
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB
        ");
    }
}
