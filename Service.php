<?php

require_once "database.php";

class Service extends Database
{
    public $service_name = "";
    public $description = "";
    public $price = "";
    public $duration = "";

    // Add new service
    public function addService()
    {
        $sql = "INSERT INTO services(service_name, description, price, duration) 
                VALUES(:service_name, :description, :price, :duration)";
        
        $query = $this->connect()->prepare($sql);

        $query->bindParam(":service_name", $this->service_name);
        $query->bindParam(":description", $this->description);
        $query->bindParam(":price", $this->price);
        $query->bindParam(":duration", $this->duration);

        return $query->execute();
    }

    // View all services
    public function viewServices($search = "")
    {
        $sql = "SELECT * FROM services WHERE service_name LIKE CONCAT('%', :search, '%') ORDER BY service_name ASC";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":search", $search);

        if ($query->execute()) {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        return [];
    }

    // Get service by ID
    public function getServiceById($id)
    {
        $sql = "SELECT * FROM services WHERE id = :id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":id", $id);

        if ($query->execute()) {
            return $query->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }

    // Edit service
    public function editService($id)
    {
        $sql = "UPDATE services SET service_name=:service_name, description=:description, 
                price=:price, duration=:duration WHERE id=:id";
        
        $query = $this->connect()->prepare($sql);

        $query->bindParam(":service_name", $this->service_name);
        $query->bindParam(":description", $this->description);
        $query->bindParam(":price", $this->price);
        $query->bindParam(":duration", $this->duration);
        $query->bindParam(":id", $id);

        return $query->execute();
    }

    // Delete service
    public function deleteService($id)
    {
        $sql = "DELETE FROM services WHERE id=:id";
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":id", $id);

        return $query->execute();
    }

    // Check if service name exists
    public function serviceExists($name, $id = null)
    {
        if ($id) {
            $sql = "SELECT COUNT(*) as total FROM services WHERE service_name=:name AND id != :id";
        } else {
            $sql = "SELECT COUNT(*) as total FROM services WHERE service_name=:name";
        }
        
        $query = $this->connect()->prepare($sql);
        $query->bindParam(":name", $name);
        
        if ($id) {
            $query->bindParam(":id", $id);
        }
        
        if ($query->execute()) {
            $result = $query->fetch();
            return $result['total'] > 0;
        }
        return false;
    }
}