<?php
ini_set("display_errors","1");
error_reporting(E_ALL);
class Presencemodel extends MY_Model {

    public function __construct() {
        parent::__construct();
        $this->current_session = $this->setting_model->getCurrentSession();
        $this->current_date = $this->setting_model->getDateYmd();
    }

    /**
     * Retourne la liste de présence des utilisateurs scanner
     * @return PresenceMDL[]
     */
    public function getAll():array
    {
        // $query = $this->db->select('*')->get("attendance");
        $query = $this->db->select()->join("staff", "staff.employee_id = attendance.employee_id")->from('attendance')->get();
        $array = [];

        foreach ($query->result_array() as $value) {
            $array[]=(new PresenceMDL())->fromArray($value);
        }
        return $array;
    }

    public function createAttendance($employee_id)
    {
        $query = $this->db->select()->from('staff')->where('employee_id', $employee_id)->get();
        if (!empty($query->result_array())) {
            $this->db->insert("attendance",["employee_id"=>$employee_id]);
            $insert_id = $this->db->insert_id();
            return $insert_id;
        }
        return 0;
    }

}

class PresenceMDL
{
    const BEGIN = 8;
    private $id;
    private $authDateTime;
    private PresenceUser $user;

    public function fromArray($data):PresenceMDL
    {
        $this->setId($data["id"]??"Null");
        $this->setAuthDateTime($data["authDateTime"]?$this->formatDate($data["authDateTime"]):"Null");
        $this->setUser((new PresenceUser())->fromArray($data));

        $morning = ($this->getAuthDateTime())->setTime(self::BEGIN,0);
        $this->setRetard($morning->getTimestamp() < $this->getAuthDateTime()->getTimestamp());
        return $this;
    }

    public function formatDate($dateString)
    {
        return new DateTimeImmutable($dateString);
    }

    /**
     * Get the value of id
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @return  self
     */ 
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of authDateTime
     */ 
    public function getFormattedAuthDateTime()
    {
        return $this->authDateTime->format('Y-m-d H:i:s');
    }

    /**
     * Get the value of authDateTime
     */ 
    public function getAuthDateTime()
    {
        return $this->authDateTime;
    }

    /**
     * Set the value of authDateTime
     *
     * @return  self
     */ 
    public function setAuthDateTime($authDateTime)
    {
        $this->authDateTime = $authDateTime;

        return $this;
    }

    /**
     * Get the value of username
     */ 
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set the value of username
     *
     * @return  self
     */ 
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get the value of user
     */ 
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set the value of user
     *
     * @return  self
     */ 
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the value of retard
     */ 
    public function getRetard()
    {
        return $this->retard;
    }

    /**
     * Set the value of retard
     *
     * @return  self
     */ 
    public function setRetard($retard)
    {
        $this->retard = $retard;

        return $this;
    }
}

class PresenceUser
{
    private $id;
    private $name;
    private $surname;
    private $department;

    public function fromArray($data):PresenceUser
    {
        $this->setId($data["id"]??"Null");
        $this->setName($data["name"]??"Null");
        $this->setSurname($data["surname"]??"Null");
        $this->setDepartment($data["department"]??"Null");
        return $this;
    }

    /**
     * Get the value of id
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set the value of id
     *
     * @return  self
     */ 
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of name
     */ 
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */ 
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of surname
     */ 
    public function getSurname()
    {
        return $this->surname;
    }

    /**
     * Set the value of surname
     *
     * @return  self
     */ 
    public function setSurname($surname)
    {
        $this->surname = $surname;

        return $this;
    }

    /**
     * Get the value of department
     */ 
    public function getDepartment()
    {
        return $this->department;
    }

    /**
     * Set the value of department
     *
     * @return  self
     */ 
    public function setDepartment($department)
    {
        $this->department = $department;

        return $this;
    }
}