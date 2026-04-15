# ระบบลงทะเบียน (Registration System)

## คุณสมบัติ (Features)
- ฟอร์มลงทะเบียนภาษาไทย
- อัพโหลดรูปโปรไฟล์
- บันทึกข้อมูลลงฐานข้อมูล MySQL
- รองรับอักษรภาษาไทย (UTF-8)

## ขั้นตอนการติดตั้ง (Installation)

### 1. สร้างฐานข้อมูล
เปิด phpMyAdmin และรันไฟล์ `database.sql` เพื่อสร้างฐานข้อมูลและตาราง

หรือใช้คำสั่ง:
```bash
mysql -u root -p < database.sql
```

### 2. ตั้งค่าการเชื่อมต่อฐานข้อมูล
แก้ไขไฟล์ `config.php` ตามข้อมูลของคุณ:
- `DB_HOST`: localhost (ค่าเริ่มต้น)
- `DB_USER`: root (ค่าเริ่มต้น)
- `DB_PASS`: รหัสผ่านของคุณ
- `DB_NAME`: security_db

### 3. สร้างโฟลเดอร์สำหรับอัพโหลดไฟล์
โฟลเดอร์ `uploads` จะถูกสร้างอัตโนมัติเมื่อมีการอัพโหลดรูปภาพ

### 4. เปิดใช้งาน
เข้าถึงผ่าน: `http://localhost/Security/register.php`

## ไฟล์ในระบบ (Files)
- `register.php` - หน้าฟอร์มลงทะเบียน
- `config.php` - การตั้งค่าฐานข้อมูล
- `success.php` - หน้าแสดงผลสำเร็จ
- `database.sql` - โครงสร้างฐานข้อมูล
- `uploads/` - โฟลเดอร์เก็บรูปภาพที่อัพโหลด

## ความต้องการ (Requirements)
- PHP 7.4 หรือสูงกว่า
- MySQL 5.7 หรือสูงกว่า
- XAMPP หรือ web server อื่นๆ
# SecurityNT-
