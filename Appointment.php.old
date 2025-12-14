<?php

require_once "database.php";

class Appointment extends Database
{
    public $user_id = "";
    public $service_id = "";
    public $staff_id = "";
    public $appointment_date = "";
    public $appointment_time = "";
    public $status = "pending";
    public $notes = "";
    
    const MAX_STAFF = 5;

    // Create new appointment
    public function bookAppointment()
    {
        $sql = "INSERT INTO appointments(user_id, service_id, staff_id, appointment_date, appointment_time, status, notes) 
                VALUES(:user_id, :service_id, :staff_id, :appointment_date, :appointment_time, :status, :notes)";
        
        $query = $this->connect()->prepare($sql);

        $query->bindParam(":user_id", $this->user_id);
        $query->bindParam(":service_id", $this->service_id);
        $query->bindParam(":staff_id", $this->staff_id);
        $query->bindParam(":appointment_date", $this->appointment_date);
        $query->bindParam(":appointment_time", $this->appointment_time);
        $query->bindParam(":status", $this->status);
        $query->bindParam(":notes", $this->notes);

        return $query->execute();
    }

    // Get user appointments
    public function getUserAppointments($user_id, $filter = "all")
    {
        $sql = "SELECT a.*, s.service_name, s.price, s.duration, u.full_name,
                staff.full_name as staff_name
                FROM appointments a 
                JOIN services s ON a.service_id = s.id 
                JOIN users u ON a.user_id = u.id 
                LEFT JOIN users staff ON a.staff_id = staff.id
                WHERE a.user_id = :user_id";
        
        if ($filter == "upcoming") {
            $sql .= " AND a.appointment_date >= CURDATE() AND a.status != 'cancelled' AND a.status != 'completed'";
        } elseif ($filter == "past") {
            $sql .= " AND (a.appointment_date < CURDATE() OR a.status = 'completed' OR a.status = 'cancelled')";
        }
        
        $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
        
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":user_id", $user_id);

        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    // Get all appointments (for admin)
    public function getAllAppointments($status = "")
    {
        $sql = "SELECT a.*, s.service_name, s.price, s.duration, u.full_name, u.email, u.phone,
                staff.full_name as staff_name
                FROM appointments a 
                JOIN services s ON a.service_id = s.id 
                JOIN users u ON a.user_id = u.id
                LEFT JOIN users staff ON a.staff_id = staff.id";
        
        if (!empty($status)) {
            $sql .= " WHERE a.status = :status";
        }
        
        $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
        
        $query = $this->connect()->prepare($sql);
        
        if (!empty($status)) {
            $query->bindParam(":status", $status);
        }

        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    // Get appointment by ID
    public function getAppointmentById($id)
    {
        $sql = "SELECT a.*, s.service_name, s.price, s.duration, u.full_name, u.email, u.phone,
                staff.full_name as staff_name
                FROM appointments a 
                JOIN services s ON a.service_id = s.id 
                JOIN users u ON a.user_id = u.id
                LEFT JOIN users staff ON a.staff_id = staff.id
                WHERE a.id = :id";
        
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":id", $id);

        if ($query->execute()) {
            return $query->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }

    // Update appointment status
    public function updateStatus($id, $status)
    {
        $sql = "UPDATE appointments SET status=:status WHERE id=:id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":status", $status);
        $query->bindParam(":id", $id);

        return $query->execute();
    }
    
    // Assign staff to appointment
    public function assignStaff($appointment_id, $staff_id)
    {
        $sql = "UPDATE appointments SET staff_id=:staff_id WHERE id=:id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":staff_id", $staff_id);
        $query->bindParam(":id", $appointment_id);

        return $query->execute();
    }

    // Reschedule appointment
    public function rescheduleAppointment($id, $date, $time)
    {
        $sql = "UPDATE appointments SET appointment_date=:date, appointment_time=:time, status='pending', staff_id=NULL WHERE id=:id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":date", $date);
        $query->bindParam(":time", $time);
        $query->bindParam(":id", $id);

        return $query->execute();
    }

    // Cancel appointment
    public function cancelAppointment($id)
    {
        return $this->updateStatus($id, 'cancelled');
    }

    // Check available staff for time slot
    public function getAvailableStaffCount($date, $time)
    {
        // Count how many staff are already booked at this time
        $sql = "SELECT COUNT(DISTINCT staff_id) as booked_staff 
                FROM appointments 
                WHERE appointment_date = :date 
                AND appointment_time = :time 
                AND staff_id IS NOT NULL
                AND status IN ('pending', 'approved')";
        
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":date", $date);
        $query->bindParam(":time", $time);
        
        if ($query->execute()) {
            $result = $query->fetch();
            $booked_staff = $result['booked_staff'];
            return self::MAX_STAFF - $booked_staff; // Return available staff count
        }
        return 0;
    }

    // Check if time slot is available (staff capacity check)
    public function isTimeSlotAvailable($date, $time, $appointment_id = null)
    {
        $available_staff = $this->getAvailableStaffCount($date, $time);
        
        // If rescheduling, we need to check if current appointment uses a staff slot
        if ($appointment_id) {
            $sql = "SELECT staff_id FROM appointments WHERE id = :id";
            $query = $this->connect()->prepare($sql);
            $query->bindParam(":id", $appointment_id);
            if ($query->execute()) {
                $current = $query->fetch();
                if ($current && $current['staff_id']) {
                    $available_staff++; // Add back the staff from current appointment
                }
            }
        }
        
        return $available_staff > 0;
    }
    
    // Get list of available staff for a time slot
    public function getAvailableStaff($date, $time)
    {
        $sql = "SELECT u.id, u.full_name, u.email 
                FROM users u
                WHERE u.role = 'staff' 
                AND u.is_available = 1
                AND u.id NOT IN (
                    SELECT staff_id FROM appointments 
                    WHERE appointment_date = :date 
                    AND appointment_time = :time 
                    AND staff_id IS NOT NULL
                    AND status IN ('pending', 'approved')
                )
                ORDER BY u.full_name";
        
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":date", $date);
        $query->bindParam(":time", $time);
        
        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }
    
    // Get all staff members
    public function getAllStaff()
    {
        $sql = "SELECT id, full_name, email, phone, is_available 
                FROM users 
                WHERE role = 'staff' 
                ORDER BY full_name";
        
        $query = $this->connect()->prepare($sql);
        
        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }
}