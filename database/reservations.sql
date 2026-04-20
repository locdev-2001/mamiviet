-- =====================================================
-- MamiViet API - Reservation System Database Schema
-- =====================================================

-- Tạo bảng reservations
DROP TABLE IF EXISTS reservations;

CREATE TABLE reservations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT 'Tên khách hàng',
    email VARCHAR(255) NOT NULL COMMENT 'Email khách hàng',
    phone VARCHAR(20) NOT NULL COMMENT 'Số điện thoại',
    persons INT NOT NULL COMMENT 'Số lượng người',
    date DATE NOT NULL COMMENT 'Ngày đặt bàn',
    time TIME NOT NULL COMMENT 'Giờ đặt bàn',
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái đặt bàn',
    admin_notes TEXT NULL COMMENT 'Ghi chú của admin',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes cho performance
    INDEX idx_reservation_date (date),
    INDEX idx_reservation_time (time),
    INDEX idx_reservation_date_time (date, time),
    INDEX idx_reservations_email (email),
    INDEX idx_reservations_phone (phone),
    INDEX idx_reservations_status (status),
    INDEX idx_reservations_created_at (created_at),
    INDEX idx_reservations_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Thêm dữ liệu mẫu (Sample Data)
-- =====================================================

INSERT INTO reservations (name, email, phone, persons, date, time, status, admin_notes) VALUES
-- Đặt bàn hôm nay
('Max Mustermann', 'max.mustermann@example.com', '+49 123 456789', 4, CURDATE(), '19:00', 'confirmed', 'Khách VIP, bàn cạnh cửa sổ'),
('Anna Schmidt', 'anna.schmidt@gmail.com', '+49 987 654321', 2, CURDATE(), '20:00', 'pending', NULL),
('Peter Weber', 'peter.weber@hotmail.com', '+49 555 123456', 6, CURDATE(), '18:30', 'confirmed', 'Sinh nhật, cần trang trí'),

-- Đặt bàn ngày mai
('Maria Garcia', 'maria.garcia@example.com', '+49 111 222333', 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '12:00', 'pending', NULL),
('Hans Mueller', 'hans.mueller@web.de', '+49 444 555666', 8, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '19:30', 'confirmed', 'Nhóm công ty'),
('Lisa Wagner', 'lisa.wagner@outlook.com', '+49 777 888999', 2, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '21:00', 'pending', NULL),

-- Đặt bàn cuối tuần
('Tom Johnson', 'tom.johnson@example.com', '+49 333 444555', 5, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '13:00', 'confirmed', 'Gia đình có trẻ nhỏ'),
('Sophie Brown', 'sophie.brown@gmail.com', '+49 666 777888', 4, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '18:00', 'pending', NULL),

-- Đặt bàn đã hủy/hoàn thành
('Michael Davis', 'michael.davis@example.com', '+49 222 333444', 3, DATE_SUB(CURDATE(), INTERVAL 1 DAY), '19:00', 'completed', 'Khách hài lòng'),
('Sarah Wilson', 'sarah.wilson@hotmail.com', '+49 888 999000', 2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), '20:30', 'cancelled', 'Khách hủy vào phút chót'),

-- Đặt bàn trong tuần tới
('Robert Taylor', 'robert.taylor@example.com', '+49 123 789456', 4, DATE_ADD(CURDATE(), INTERVAL 5 DAY), '19:30', 'pending', NULL),
('Emma Anderson', 'emma.anderson@gmail.com', '+49 456 123789', 6, DATE_ADD(CURDATE(), INTERVAL 6 DAY), '18:00', 'confirmed', 'Kỷ niệm ngày cưới'),
('David Martinez', 'david.martinez@web.de', '+49 789 456123', 2, DATE_ADD(CURDATE(), INTERVAL 7 DAY), '20:00', 'pending', NULL);

-- =====================================================
-- Stored Procedures & Views (Optional)
-- =====================================================

-- View để xem thống kê nhanh
CREATE OR REPLACE VIEW reservation_statistics AS
SELECT
    COUNT(*) as total_reservations,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
    COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_count,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_count,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
    COUNT(CASE WHEN date = CURDATE() THEN 1 END) as today_reservations,
    COUNT(CASE WHEN date >= CURDATE() THEN 1 END) as upcoming_reservations,
    AVG(persons) as avg_party_size,
    SUM(persons) as total_persons
FROM reservations;

-- View cho đặt bàn hôm nay
CREATE OR REPLACE VIEW todays_reservations AS
SELECT
    id,
    name,
    email,
    phone,
    persons,
    time,
    status,
    admin_notes,
    created_at
FROM reservations
WHERE date = CURDATE()
ORDER BY time ASC;

-- View cho đặt bàn sắp tới
CREATE OR REPLACE VIEW upcoming_reservations AS
SELECT
    id,
    name,
    email,
    phone,
    persons,
    date,
    time,
    status,
    admin_notes,
    created_at
FROM reservations
WHERE date >= CURDATE() AND status IN ('pending', 'confirmed')
ORDER BY date ASC, time ASC;

-- =====================================================
-- Stored Procedure: Kiểm tra availability
-- =====================================================

DELIMITER //

CREATE PROCEDURE CheckReservationAvailability(
    IN p_date DATE,
    IN p_time TIME,
    IN p_persons INT,
    OUT p_available BOOLEAN,
    OUT p_remaining_capacity INT
)
BEGIN
    DECLARE v_existing_persons INT DEFAULT 0;
    DECLARE v_max_capacity_per_slot INT DEFAULT 50; -- Có thể điều chỉnh

    -- Tính tổng số người đã đặt trong cùng ngày/giờ
    SELECT COALESCE(SUM(persons), 0)
    INTO v_existing_persons
    FROM reservations
    WHERE date = p_date
      AND time = p_time
      AND status IN ('pending', 'confirmed');

    -- Tính remaining capacity
    SET p_remaining_capacity = v_max_capacity_per_slot - v_existing_persons;

    -- Kiểm tra có đủ chỗ không
    SET p_available = (p_remaining_capacity >= p_persons);
END //

DELIMITER ;

-- =====================================================
-- Triggers (Optional)
-- =====================================================

-- Trigger để log thay đổi status
DROP TABLE IF EXISTS reservation_status_logs;

CREATE TABLE reservation_status_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reservation_id BIGINT UNSIGNED NOT NULL,
    old_status ENUM('pending', 'confirmed', 'cancelled', 'completed') NULL,
    new_status ENUM('pending', 'confirmed', 'cancelled', 'completed') NOT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL,

    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    INDEX idx_reservation_logs_reservation_id (reservation_id),
    INDEX idx_reservation_logs_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Trigger cho việc update status
DELIMITER //

CREATE TRIGGER reservation_status_change_log
    AFTER UPDATE ON reservations
    FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO reservation_status_logs (reservation_id, old_status, new_status, notes)
        VALUES (NEW.id, OLD.status, NEW.status, NEW.admin_notes);
    END IF;
END //

DELIMITER ;

-- =====================================================
-- Indexes bổ sung cho performance
-- =====================================================

-- Composite index cho việc tìm kiếm theo email + status
CREATE INDEX idx_reservations_email_status ON reservations(email, status);

-- Composite index cho việc tìm kiếm theo phone + status
CREATE INDEX idx_reservations_phone_status ON reservations(phone, status);

-- Index cho việc search theo tên
CREATE INDEX idx_reservations_name ON reservations(name);

-- =====================================================
-- Comments & Documentation
-- =====================================================

-- Thêm comment cho bảng
ALTER TABLE reservations COMMENT = 'Bảng quản lý đặt bàn nhà hàng MamiViet';

-- =====================================================
-- Test Queries (để kiểm tra)
-- =====================================================

-- Kiểm tra dữ liệu đã insert
SELECT 'Total reservations:' as info, COUNT(*) as count FROM reservations
UNION ALL
SELECT 'Today reservations:', COUNT(*) FROM reservations WHERE date = CURDATE()
UNION ALL
SELECT 'Pending reservations:', COUNT(*) FROM reservations WHERE status = 'pending'
UNION ALL
SELECT 'Confirmed reservations:', COUNT(*) FROM reservations WHERE status = 'confirmed';

-- Test availability check
CALL CheckReservationAvailability(CURDATE(), '19:00:00', 4, @available, @remaining);
SELECT
    CURDATE() as check_date,
    '19:00' as check_time,
    4 as requested_persons,
    @available as available,
    @remaining as remaining_capacity;

-- Xem thống kê
SELECT * FROM reservation_statistics;

-- Xem đặt bàn hôm nay
SELECT * FROM todays_reservations;

-- =====================================================
-- HOÀN THÀNH!
-- =====================================================

SELECT 'MamiViet Reservation System Database Created Successfully!' as status;