<?php

class ToolsController extends Zend_Controller_Action {

    /**
     *
     * @var Application_Model_FirstnameService 
     */
    protected $firstnameService;

    /**
     * get service ready
     */
    public function preDispatch() {
        $this->firstnameService = new Application_Model_FirstnameService();
    }

    /**
     * init controller 
     */
    public function init() {
        /* Initialize action controller here */
    }

    /**
     * scannt Vornamensdaten und legt sie in der DB ab 
     */
    public function scrapeAction() {

        if ($this->firstnameService->GetMaxYear() >= 2011) {
            //nothing to scrape
        } else {
            require_once("Parser/simple_html_dom.php");
            $filter = new Zend_Filter_StripTags(); // filter for cleaning data
            $start = $this->firstnameService->GetMaxRankYearInDb();
            $ende = $start + 20;
            $max = $this->firstnameService->GetMaxYear();
            if ($ende > $max) {
                $ende = $max;
            }

            $limits = array($start, $ende);
            $sexes = array('f', 'm');

            for ($currLimit = $limits[0]; $currLimit <= $limits[1]; $currLimit++) {

                $url = "http://www.beliebte-vornamen.de/jahrgang/j" . $currLimit;
                $namesArr = array();
                $html = file_get_html($url);

                echo "<h1>" . $currLimit . "</h1>";
                $counter = 0;
                foreach ($html->find('ol') as $list) {
                    if ($counter <= 1) {
                        foreach ($list->find('li') as $value) {
                            $rankcounter = 1;
                            $nameRaw = $value->plaintext;
                            echo "<p>" . $nameRaw . "</p>";
                            if (stristr($nameRaw, '/')) {
                                $temp = explode('/', $nameRaw);
                                foreach ($temp as $wert) {
                                    $namesArr[$sexes[$counter]][] = array("name" => $filter->filter(trim(html_entity_decode($wert, ENT_NOQUOTES, 'UTF-8'))),
                                        "rank" => $rankcounter);
                                }
                            } else {
                                $namesArr[$sexes[$counter]][] = array("name" => $filter->filter(trim(html_entity_decode($nameRaw, ENT_NOQUOTES, 'UTF-8'))),
                                    "rank" => $rankcounter);
                            }
                            $rankcounter++;
                        }
                    }
                    $counter++;
                }

                foreach ($namesArr as $sex => $values) {
                    foreach ($values as $namesValues) {
                        $firstnameId = $this->firstnameService->NewFirstname($namesValues["name"], $sex);
                        if ($firstnameId != 0) {
                            // enter rank
                            $this->firstnameService->NewRank($firstnameId, $currLimit, $namesValues["rank"]);
                        }
                    }
                }
            }
        }
    }

}

