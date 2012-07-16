<?php

class Zend_View_Helper_Errors extends Zend_View_Helper_Abstract {

    /**
     * Outputs errors using a uniform format
     *
     * @param Array $errors
     * @return nil
     */
    public function Errors($errorValues) {

        if (isset($errorValues) && !empty($errorValues)) {
            echo '<div class="alert alert-error">';
            foreach ($errorValues AS $feldName => $errors) {
                if (isset($errors) && is_array($errors) && !empty($errors)) {
                    foreach ($errors as $error) {
                        echo $error;
                    }
                } elseif (isset($errors) && !is_array($errors)) {
                    echo $errors;
                }
            }
            echo "</div>";
        }
    }

}