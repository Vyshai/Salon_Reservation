<?php

require_once "database.php";

class User extends Database
{
    public $full_name = "";
    public $email = "";
    public $password = "";
    public $phone = "";
    public $role = "customer";

    // Register new user with email verification
    public function registerWithVerification($verification_token, $token_expiry)
    {
        $sql = "INSERT INTO users(full_name, email, password, phone, role, is_verified, verification_token, token_expiry) 
                VALUES(:full_name, :email, :password, :phone, :role, 0, :token, :expiry)";
        
        $query = $this->connect()->prepare($sql);
        
        $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);
        
        $query->bindParam(":full_name", $this->full_name);
        $query->bindParam(":email", $this->email);
        $query->bindParam(":password", $hashedPassword);
        $query->bindParam(":phone", $this->phone);
        $query->bindParam(":role", $this->role);
        $query->bindParam(":token", $verification_token);
        $query->bindParam(":expiry", $token_expiry);

        return $query->execute();
    }

    // Original register method (for backward compatibility)
    public function register()
    {
        $sql = "INSERT INTO users(full_name, email, password, phone, role, is_verified) 
                VALUES(:full_name, :email, :password, :phone, :role, 1)";
        
        $query = $this->connect()->prepare($sql);
        
        $hashedPassword = password_hash($this->password, PASSWORD_DEFAULT);
        
        $query->bindParam(":full_name", $this->full_name);
        $query->bindParam(":email", $this->email);
        $query->bindParam(":password", $hashedPassword);
        $query->bindParam(":phone", $this->phone);
        $query->bindParam(":role", $this->role);

        return $query->execute();
    }

    // Verify email with token
    public function verifyEmail($token)
    {
        // Find user with this token
        $sql = "SELECT id, is_verified, token_expiry FROM users WHERE verification_token = :token";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":token", $token);
        
        if ($query->execute()) {
            $user = $query->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return false; // Invalid token
            }
            
            if ($user['is_verified'] == 1) {
                return 'already_verified';
            }
            
            // Check if token expired
            if (strtotime($user['token_expiry']) < time()) {
                return 'expired';
            }
            
            // Verify the user
            $updateSql = "UPDATE users SET is_verified = 1, verification_token = NULL, token_expiry = NULL WHERE id = :id";
            $updateQuery = $this->connect()->prepare($updateSql);
            $updateQuery->bindParam(":id", $user['id']);
            
            if ($updateQuery->execute()) {
                return true;
            }
        }
        
        return false;
    }

    // Resend verification email
    public function resendVerification($email)
    {
        $sql = "SELECT id, is_verified FROM users WHERE email = :email";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":email", $email);
        
        if ($query->execute()) {
            $user = $query->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                return 'not_found';
            }
            
            if ($user['is_verified'] == 1) {
                return 'already_verified';
            }
            
            // Generate new token
            $verification_token = bin2hex(random_bytes(32));
            $token_expiry = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            $updateSql = "UPDATE users SET verification_token = :token, token_expiry = :expiry WHERE id = :id";
            $updateQuery = $this->connect()->prepare($updateSql);
            $updateQuery->bindParam(":token", $verification_token);
            $updateQuery->bindParam(":expiry", $token_expiry);
            $updateQuery->bindParam(":id", $user['id']);
            
            if ($updateQuery->execute()) {
                return $verification_token;
            }
        }
        
        return false;
    }

    // Check if email already exists
    public function emailExists($email)
    {
        $sql = "SELECT COUNT(*) as total FROM users WHERE email = :email";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":email", $email);
        
        if ($query->execute()) {
            $result = $query->fetch();
            return $result['total'] > 0;
        }
        return false;
    }

    // Login user (updated to check verification)
    public function login($email, $password)
    {
        $sql = "SELECT * FROM users WHERE email = :email";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":email", $email);
        
        if ($query->execute()) {
            $user = $query->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Check if email is verified (except for admin and staff)
                if ($user['role'] == 'customer' && $user['is_verified'] == 0) {
                    return 'not_verified';
                }
                return $user;
            }
        }
        return false;
    }

    // Get user by ID
    public function getUserById($id)
    {
        $sql = "SELECT * FROM users WHERE id = :id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":id", $id);
        
        if ($query->execute()) {
            return $query->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }

    // Get all users
    public function getAllUsers()
    {
        $sql = "SELECT id, full_name, email, phone, role, is_verified, is_available, created_at 
                FROM users ORDER BY created_at DESC";
        $query = $this->connect()->prepare($sql);
        
        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    public function getUserByEmail($email)
    {
        $sql = "SELECT * FROM users WHERE email = :email";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":email", $email);
    
        if ($query->execute()) {
            return $query->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }
}

