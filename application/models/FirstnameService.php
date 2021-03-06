<?php

class Application_Model_FirstnameService {

    /**
     *
     * @var Application_Model_DbTable_DataFirstnames 
     */
    protected $firstnames;

    /**
     *
     * @var Application_Model_DbTable_DataFirstnamesRank 
     */
    protected $firstnamesRank;

    /**
     *
     * @var int
     * earliest year of firstname data import 
     */
    private $minYear = 1890;

    /**
     *
     * @var int
     * maximum year of firstname data 
     */
    private $maxYear = 2011;

    function __construct(Application_Model_DbTable_DataFirstnames $firstnameTableModel,Application_Model_DbTable_DataFirstnamesRank $firstnameRankTableModel) {
        //// database muss gar nicht extra definiert werden - das geht out of the box mit den richtigen werten in der application.ini
        $this->firstnames = $firstnameTableModel;
        $this->firstnamesRank = $firstnameRankTableModel;
    }

    /**
     *
     * @param string $name
     * @param string $sex
     * @return int 
     */
    public function NewFirstname($name, $sex = 'f') {

        $rowObj = $this->GetFirstnameByNameAndSex($name, $sex);
        if ($rowObj == null) {
            $params = array(
                'name' => $name,
                'sex' => $sex);
            return $this->firstnames->insert($params);
        } else {
            return $rowObj->id;
        }
    }

    /**
     *
     * @param int $id
     * @param int $year
     * @param int $rank 
     * @return bool
     */
    public function NewRank($id, $year, $rank) {
        $rowObj = $this->GetRankByYear($id, $year);
        if ($rowObj == null) {
            $params = array(
                'data_firstnames_id' => $id,
                'jahr' => $year,
                'rank' => $rank);
            $erg = $this->firstnamesRank->insert($params);
            if (is_array($erg) && !empty($erg)) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param int $year
     * @return object 
     */
    public function GetFirstnamesByYear($year) {
        $select = $this->firstnames->select();
        $select->order('name');
        $select->where($this->firstnames->getAdapter()->quoteInto('jahr = ?', $year));
        return $this->firstnames->fetchAll($select);
    }

    /**
     *
     * @return int 
     */
    public function GetMaxRankYearInDb() {
        $select = $this->firstnamesRank->select();
        $select->order('jahr DESC');
        $select->limit(1);

        $ergRow = $this->firstnamesRank->fetchRow($select);
        if (!empty($ergRow))
            return $ergRow->jahr;
        else
            return $this->minYear;
    }

    public function GetMaxYear() {
        return $this->maxYear;
    }

    public function GetFirstnameByName($name) {
        $where = $this->firstnames->getAdapter()->quoteInto('LOWER(name) = ?', strtolower($name));
        return $this->firstnames->fetchRow($where);
    }

    /**
     *
     * @param string $name
     * @param string $sex
     * @return Zend_Db_Table_Row_Abstract 
     */
    public function GetFirstnameByNameAndSex($name, $sex) {
        $where = $this->firstnames->getAdapter()->quoteInto('LOWER(name) = ? AND ', strtolower($name)) .
                $this->firstnames->getAdapter()->quoteInto('LOWER(sex) = ?', strtolower($sex));
        return $this->firstnames->fetchRow($where);
    }

    /**
     *
     * @param int $id
     * @param int $year
     * @return Zend_Db_Table_Row_Abstract 
     */
    public function GetRankByYear($id, $year) {
        $where = $this->firstnamesRank->getAdapter()->quoteInto('data_firstnames_id = ? AND ', $id) .
                $this->firstnamesRank->getAdapter()->quoteInto('jahr = ?', $year);
        return $this->firstnamesRank->fetchRow($where);
    }

}
