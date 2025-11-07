-- ======================================================
-- Database: mutadarrib (مثال اسم قاعدة البيانات)
-- ======================================================
CREATE DATABASE IF NOT EXISTS mutadarrib
  CHARACTER SET = 'utf8mb4'
  COLLATE = 'utf8mb4_general_ci';
USE mutadarrib;

-- ======================================================
-- Table: users (كل الحسابات: طلاب، مزاوليين، أدمن)
-- ======================================================
CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(200) NOT NULL,
  national_id VARCHAR(30) NOT NULL,
  phone VARCHAR(30),
  email VARCHAR(150) NOT NULL,
  address VARCHAR(255),
  password VARCHAR(255) NOT NULL, -- خزّن كلمات المرور مشفرة (bcrypt) في التطبيق
  role ENUM('student','lawyer','admin') NOT NULL DEFAULT 'student',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY ux_users_national_id (national_id),
  UNIQUE KEY ux_users_email (email),
  INDEX ix_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ======================================================
-- Table: students (بيانات خاصة بالطالب)
-- ======================================================
CREATE TABLE students (
  student_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  highschool_certificate ENUM('نعم','لا') NOT NULL DEFAULT 'لا',
  university_degree ENUM('بكالوريوس','ماجستير','دكتوراه') DEFAULT NULL,
  no_conviction_doc VARCHAR(255) DEFAULT NULL,
  good_conduct_doc VARCHAR(255) DEFAULT NULL,
  social_security ENUM('نعم','لا') NOT NULL DEFAULT 'لا',
  social_security_number VARCHAR(100) DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY ux_students_user_id (user_id),
  CONSTRAINT chk_students_social_security CHECK (
    (social_security = 'نعم' AND social_security_number IS NOT NULL)
    OR (social_security = 'لا' AND social_security_number IS NULL)
  ),
  CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ======================================================
-- Table: lawyers_master (الجدول المرجعي للمزاولين المعتمدين / من النقابة)
-- ======================================================
CREATE TABLE lawyers_master (
  master_id INT AUTO_INCREMENT PRIMARY KEY,
  lawyer_name VARCHAR(200) NOT NULL,
  national_id VARCHAR(30) NOT NULL,
  office_address VARCHAR(255),
  phone VARCHAR(30),
  email VARCHAR(150),
  notes TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY ux_lm_national_id (national_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ======================================================
-- Table: lawyers (المزاولون المسجلون في التطبيق)
-- ======================================================
CREATE TABLE lawyers (
  lawyer_id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  master_id INT DEFAULT NULL, -- إذا تم التحقق وربط بسجل من lawyers_master
  office_address VARCHAR(255),
  verified TINYINT(1) NOT NULL DEFAULT 0, -- 1 = تم التحقق/موثوق
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY ux_lawyers_user_id (user_id),
  CONSTRAINT fk_lawyers_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_lawyers_master FOREIGN KEY (master_id) REFERENCES lawyers_master(master_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ======================================================
-- Table: trainings (فرص التدريب المنشورة)
-- ======================================================
CREATE TABLE trainings (
  training_id INT AUTO_INCREMENT PRIMARY KEY,
  lawyer_id INT NOT NULL,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  duration_months INT DEFAULT NULL,
  location VARCHAR(255),
  start_date DATE DEFAULT NULL,
  end_date DATE DEFAULT NULL,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  seats INT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_trainings_lawyer FOREIGN KEY (lawyer_id) REFERENCES lawyers(lawyer_id) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX ix_trainings_status (status),
  INDEX ix_trainings_lawyer (lawyer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ======================================================
-- Table: training_applications (طلبات الطلاب على التدريب)
-- ======================================================
CREATE TABLE training_applications (
  application_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  training_id INT NOT NULL,
  status ENUM('pending','accepted','rejected','completed') NOT NULL DEFAULT 'pending',
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at DATETIME DEFAULT NULL,
  notes TEXT,
  CONSTRAINT fk_apps_student FOREIGN KEY (student_id) REFERENCES students(student_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_apps_training FOREIGN KEY (training_id) REFERENCES trainings(training_id) ON DELETE CASCADE ON UPDATE CASCADE,
  INDEX ix_apps_status (status),
  INDEX ix_apps_student (student_id),
  INDEX ix_apps_training (training_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ======================================================
-- Optional: جدول لتسجيل النشاطات (Audit) - مفيد للتتبّع
-- ======================================================
CREATE TABLE audit_logs (
  log_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  entity VARCHAR(100),
  entity_id VARCHAR(100),
  action VARCHAR(50),
  performed_by INT NULL,
  details JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_audit_user FOREIGN KEY (performed_by) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ======================================================
-- Trigger: when application status becomes 'completed'
-- -> تحويل الدور إلى 'lawyer' وإنشاء سجل في جدول lawyers إذا لم يكن موجوداً
-- ======================================================
DELIMITER $$

DROP TRIGGER IF EXISTS trg_after_app_completed$$

CREATE TRIGGER trg_after_app_completed
AFTER UPDATE ON training_applications
FOR EACH ROW
BEGIN
  DECLARE v_user_id INT DEFAULT NULL;
  DECLARE v_exists INT DEFAULT 0;
  -- نتحقق أن الحالة تحولت إلى 'completed' من حالة أخرى
  IF (NEW.status = 'completed' AND OLD.status <> 'completed') THEN
    -- نحصل على user_id من جدول students
    SELECT user_id INTO v_user_id FROM students WHERE student_id = NEW.student_id LIMIT 1;
    IF v_user_id IS NOT NULL THEN
      -- نحدّد ما إذا كان المستخدم موجودًا بالفعل كمزاول
      SELECT COUNT(*) INTO v_exists FROM lawyers WHERE user_id = v_user_id;
      IF v_exists = 0 THEN
        -- نعيد تحديث دور المستخدم إلى 'lawyer'
        UPDATE users SET role = 'lawyer', updated_at = NOW() WHERE user_id = v_user_id AND role <> 'lawyer';
        -- ندرج سجل في جدول lawyers (مع ترك master_id فارغًا - يمكن ربطه لاحقًا بالتحقق)
        INSERT INTO lawyers (user_id, office_address, verified, created_at) VALUES (v_user_id, NULL, 0, NOW());
      ELSE
        -- لو كان موجودًا كـ lawyer بالفعل، نضمن بأن الدور في users هو 'lawyer'
        UPDATE users SET role = 'lawyer', updated_at = NOW() WHERE user_id = v_user_id AND role <> 'lawyer';
      END IF;
    END IF;
  END IF;
END$$

DELIMITER ;

-- ======================================================
-- بعض بيانات المثال (اختياري) — يمكنك حذفها أو تعديلها
-- ======================================================
-- إدراج مشرف افتراضي (كلمة المرور: استخدم قيمة مشفرة في التطبيق!)
INSERT INTO users (full_name, national_id, phone, email, address, password, role)
VALUES ('Admin System', '0000000000', '0000000', 'admin@example.com', 'Head Office', '$2y$12$EXAMPLEHASH', 'admin');

-- مثال سجل مزاول رئيسي (من نقابة/قائمة موثوقة)
INSERT INTO lawyers_master (lawyer_name, national_id, office_address, phone, email, notes)
VALUES ('محمد المحامي', '1111111111', 'عمان - شارع الحسين', '0790000000', 'lawyer1@example.com', 'مسجل لدى النقابة');

-- مثال مستخدم طالب
INSERT INTO users (full_name, national_id, phone, email, address, password, role)
VALUES ('أحمد الطالب', '2222222222', '0791111111', 'student1@example.com', 'عمان - الرمثا', '$2y$12$EXAMPLEHASH', 'student');

-- ربط بيانات الطالب
INSERT INTO students (user_id, highschool_certificate, university_degree, social_security, social_security_number)
VALUES (LAST_INSERT_ID(), 'نعم', 'بكالوريوس', 'لا', NULL);


ALTER TABLE lawyers
ADD COLUMN password VARCHAR(255) NOT NULL AFTER office_address;
