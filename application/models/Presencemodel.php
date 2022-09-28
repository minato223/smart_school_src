<?php

use function PHPSTORM_META\type;

ini_set("display_errors", "1");
error_reporting(E_ALL);

class Presencemodel extends MY_Model
{
    //Rôles enseignant id
    const PRIMARY_KEY = 7;
    //Rôles maternel id
    const MATERNAL_KEY = 4;

    //Heures de début et fin
    public TimeLine $primary_timeline;
    public TimeLine $maternal_timeline;

    /**
     * Les exceptions
     * @var array
     */
    public $exceptions = [];

    const PROF_CATEGORIES = [
        self::PRIMARY_KEY => "Enseignant du Primaire",
        self::MATERNAL_KEY => "Enseignant de la Maternelle",
    ];
    public function __construct()
    {
        parent::__construct();
        $this->current_session = $this->setting_model->getCurrentSession();
        $this->current_date = $this->setting_model->getDateYmd();
        $this->primary_timeline = new TimeLine(
            TimeLine::PRIMARY_BEGIN_HOUR,
            TimeLine::PRIMARY_BEGIN_MINUTE,
            TimeLine::PRIMARY_END_HOUR,
            TimeLine::PRIMARY_END_MINUTE
        );
        $this->maternal_timeline = new TimeLine(
            TimeLine::MATERNAL_BEGIN_HOUR,
            TimeLine::MATERNAL_BEGIN_MINUTE,
            TimeLine::MATERNAL_END_HOUR,
            TimeLine::MATERNAL_END_MINUTE
        );
        $this->exceptions = [
            //Tiguidankè Fomba
            1 => new TimeLine(TimeLine::PRIMARY_BEGIN_HOUR, 40, 15, 0),
            //Macoro
            2 => new TimeLine(8, 30, TimeLine::MATERNAL_END_HOUR, TimeLine::MATERNAL_END_MINUTE),
            //Mme Goro
            3 => new TimeLine(8, 30, 14, 0),
            //Safiatou
            4 => new TimeLine(8, 30, TimeLine::MATERNAL_END_HOUR, TimeLine::MATERNAL_END_MINUTE),
            //Germaine
            5 => new TimeLine(TimeLine::MATERNAL_BEGIN_HOUR, TimeLine::MATERNAL_BEGIN_MINUTE, 15, 0),
            //Mme Touré
            6 => new TimeLine(TimeLine::MATERNAL_BEGIN_HOUR, TimeLine::MATERNAL_BEGIN_MINUTE, 15, 0),
        ];
    }

    /**
     * Retourne la liste de présence des élèves scanner
     * @return PresenceMDL[]
     */
    public function getAttendance($attendance_type = null, $date = null, $prof_category = null): array
    {
        $is_student = strtolower($attendance_type) === "student";
        if ($prof_category == null) {
            $prof_category = self::PRIMARY_KEY;
        } else {
            if (!isset(self::PROF_CATEGORIES[$prof_category])) {
                $prof_category = self::PRIMARY_KEY;
            }
        }
        $sql = "SELECT * FROM attendance JOIN staff ON staff.employee_id = attendance.employee_id JOIN staff_roles ON staff_roles.staff_id = staff.id AND staff_roles.role_id = '$prof_category'";
        if ($is_student) {
            $sql = "SELECT * 
            FROM attendance JOIN students ON students.admission_no = attendance.employee_id";
        }
        if ($date !== null) {
            $sql .= " WHERE authDateTime BETWEEN '$date 00:00:00' AND '$date 23:59:59'";
        } else {
            $sql .= " WHERE DATE(authDateTime) = CURDATE()";
        }
        // echo json_encode($this->db->query($sql)->result_array());
        // die();
        $array = [];
        try {
            $query = $this->db->query($sql);
            foreach ($query->result_array() as $value) {
                $array[] = (new PresenceMDL())->fromArray($value, $is_student);
            }
        } catch (\Throwable $th) {
            die("Une exception");
        }
        return $array;
    }

    public function createAttendance($employee_id)
    {
        $message = "Aucune correspondance trouvée";
        $today_attendance_query = $this->db->query("SELECT * FROM attendance WHERE DATE(authDateTime) = CURDATE() AND employee_id = '$employee_id'");
        $attendanceResult = $today_attendance_query->result_array();
        $attendanceExist = count($attendanceResult) > 0;
        $sql = "SELECT * FROM staff JOIN staff_roles ON staff_roles.staff_id = staff.id AND staff.employee_id = '$employee_id'";
        $user_query = $this->db->query($sql);
        if (!empty($user_query->result_array())) {
            $staffUser = $user_query->result_array()[0];
            if ($attendanceExist) {
                $isMaternalStaff = $staffUser["role_id"] == Presencemodel::MATERNAL_KEY;
                $success_message = "Au revoir " . $staffUser["name"] ?? "" . "" . $staffUser["surname"] ?? "";
                if ($isMaternalStaff) {
                    return $this->updateAttendance(
                        TimeLine::MATERNAL_END_HOUR,
                        TimeLine::MATERNAL_END_MINUTE,
                        $attendanceResult[0],
                        $message,
                        $success_message
                    );
                } else {
                    return $this->updateAttendance(
                        TimeLine::PRIMARY_END_HOUR,
                        TimeLine::PRIMARY_END_MINUTE,
                        $attendanceResult[0],
                        $message,
                        $success_message,
                    );
                }
            } else {
                $insert_id = $this->inserAttendance($employee_id);
                $message = "Bonjour , bienvenu " . $staffUser["name"] ?? "" . "" . $staffUser["surname"] ?? "";
                return [$insert_id, $message];
            }
        }
        $user_query = $this->db->select()->from('students')->where('admission_no', $employee_id)->get();


        if (!empty($user_query->result_array())) {
            $user = $user_query->result_array()[0];
            if ($attendanceExist) {
                $success_message = "Au revoir " . $user["firstname"] ?? "" . "" . $user["middlename"] ?? "" . "" . $user["lastname"] ?? "";
                return $this->updateAttendance(
                    TimeLine::STUDENT_END_HOUR,
                    TimeLine::STUDENT_END_MINUTE,
                    $attendanceResult[0],
                    $message,
                    $success_message,
                );
            } else {
                $insert_id = $this->inserAttendance($employee_id);
                $message = "Bonjour " . $user["firstname"] ?? "" . "" . $user["middlename"] ?? "" . "" . $user["lastname"] ?? "";
                return [$insert_id, $message];
            }
        }
        return [0, $message];
    }

    public function inserAttendance($employee_id): int
    {
        $now = TimeLine::getCurrentDate();
        $formatedDate = TimeLine::formatDate($now);
        $this->db->insert("attendance", ["employee_id" => $employee_id, "authDateTime" => $formatedDate]);
        return $this->db->insert_id();
    }

    public function updateAttendance(int $end_hour, int $end_minute, array $attendance_result, string $message, string $success_message)
    {
        $now = TimeLine::getCurrentDate();
        $formatedDate = TimeLine::formatDate($now);
        $defaultEndTime = (TimeLine::getCurrentDate())->setTime($end_hour, $end_minute);
        $canScan = $defaultEndTime->getTimestamp() < $now->getTimestamp();
        if ($canScan) {
            $sql = sprintf("UPDATE attendance SET exitDateTime = '$formatedDate' WHERE id = '%d'", $attendance_result["id"]);
            $updateQuery = $this->db->query($sql);
            if ($updateQuery) {
                return [1, $success_message];
            } else {
                return [0, $message];
            }
        } else {
            return [-1, "Vous êtes déjà enrégisté aujourd'hui"];
        }
    }
}

class TimeLine
{
    const DATE_FORMAT = "Y-m-d H:i:s";
    const SERVER_DIFF = 2;
    //Heures par defaut
    const STUDENT_BEGIN_HOUR = 8;
    const STUDENT_BEGIN_MINUTE = 0;
    const STUDENT_END_HOUR = 17;
    const STUDENT_END_MINUTE = 0;


    const PRIMARY_BEGIN_HOUR = 7;
    const PRIMARY_BEGIN_MINUTE = 35;
    const PRIMARY_END_HOUR = 14;
    const PRIMARY_END_MINUTE = 0;

    const MATERNAL_BEGIN_HOUR = 7;
    const MATERNAL_BEGIN_MINUTE = 10;
    const MATERNAL_END_HOUR = 16;
    const MATERNAL_END_MINUTE = 30;

    public int $begin_hour;
    public int $begin_minute;
    public int $end_hour;
    public int $end_minute;
    public function __construct(
        int $begin_hour,
        int $begin_minute,
        int $end_hour,
        int $end_minute
    ) {
        $this->begin_hour = $begin_hour;
        $this->begin_minute = $begin_minute;
        $this->end_hour = $end_hour;
        $this->end_minute = $end_minute;
    }

    static public function getCurrentDate(): DateTimeImmutable
    {
        $date = (new DateTimeImmutable())->format(self::DATE_FORMAT);
        $newDate = date(self::DATE_FORMAT, strtotime(sprintf('%s -%d hours', $date, self::SERVER_DIFF)));
        return new DateTimeImmutable($newDate);
    }
    static public function getDate(string $dateString): DateTimeImmutable
    {
        return new DateTimeImmutable($dateString);
    }
    static public function formatDate(DateTimeImmutable $date): string
    {
        return $date->format(self::DATE_FORMAT);
    }
}

class PresenceMDL
{
    private $id;
    private $authDateTime;
    private $exitDateTime;
    private PresenceUser $user;

    public function fromArray($data, bool $is_student): PresenceMDL
    {
        $this->setId($data["id"] ?? "Null");
        $this->setAuthDateTime($data["authDateTime"] ? $this->formatDate($data["authDateTime"]) : "Null");
        $this->setExitDateTime($data["exitDateTime"] ? $this->formatDate($data["exitDateTime"]) : null);
        $this->setUser((new PresenceUser())->fromArray($data));
        if ($is_student) {
            $morning = ($this->getAuthDateTime())->setTime(TimeLine::STUDENT_BEGIN_HOUR, TimeLine::STUDENT_BEGIN_MINUTE);
        } else {
            $presence_model = new Presencemodel();
            if (isset($presence_model->exceptions[$this->getId()])) {
                /** @var TimeLine */
                $timeline = $presence_model->exceptions[$this->getId()];
                $morning = ($this->getAuthDateTime())->setTime($timeline->begin_hour, $timeline->begin_minute);
            } else {
                if ($this->getUser()->getRole_id() == $presence_model::MATERNAL_KEY) {
                    $morning = ($this->getAuthDateTime())->setTime(TimeLine::MATERNAL_BEGIN_HOUR, TimeLine::MATERNAL_BEGIN_MINUTE);
                } else {
                    $morning = ($this->getAuthDateTime())->setTime(TimeLine::PRIMARY_BEGIN_HOUR, TimeLine::PRIMARY_BEGIN_MINUTE);
                }
            }
        }
        $this->setRetard($morning->getTimestamp() < $this->getAuthDateTime()->getTimestamp());
        return $this;
    }

    static public function formatDate($dateString)
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
        if ($this->getAuthDateTime() === null) {
            return "Null";
        }
        return explode(" ", $this->getAuthDateTime()->format(TimeLine::DATE_FORMAT))[1];
    }

    /**
     * Get the value of authDateTime
     */
    public function getFormattedExitDateTime()
    {
        if ($this->getExitDateTime() === null) {
            return "Null";
        }
        return explode(" ", $this->getExitDateTime()->format(TimeLine::DATE_FORMAT))[1];
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
     * Get the value of exitDateTime
     */
    public function getExitDateTime()
    {
        return $this->exitDateTime;
    }

    /**
     * Set the value of exitDateTime
     *
     * @return  self
     */
    public function setExitDateTime($exitDateTime)
    {
        $this->exitDateTime = $exitDateTime;

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
    private $role_id;

    public function fromArray($data): PresenceUser
    {
        $this->setId($data["id"] ?? "Null");
        $this->setName($data["name"] ?? $data["firstname"] ?? "Null");
        $this->setSurname($data["surname"] ?? $data["lastname"] ?? "Null");
        $this->setDepartment($data["department"] ?? "Null");
        $this->setRole_id($data["role_id"] ?? 0);
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

    /**
     * Get the value of role_id
     */
    public function getRole_id()
    {
        return $this->role_id;
    }

    /**
     * Set the value of role_id
     *
     * @return  self
     */
    public function setRole_id($role_id)
    {
        $this->role_id = $role_id;

        return $this;
    }
}
