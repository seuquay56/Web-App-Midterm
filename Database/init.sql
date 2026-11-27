-- Test DB
CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'pending',
    priority INT NOT NULL DEFAULT 1,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO tasks (title, status, priority, content) VALUES 
('Kiem tra ket noi API', 'in_progress', 1, 'Test endpoint CRUD dau tien tu file init.sql'),
('Hoan thien giao dien', 'pending', 2, 'Ghep API danh sach ghi chu len frontend');