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
            echo '<div class="alert-message error">';
            foreach ($errorValues AS $feldName => $errors) {
                if (isset($errors) && is_array($errors) && !empty($errors)) {
                    foreach ($errors as $error) {
                        printf("<p>%s</p>", $error);
                    }
                } elseif (isset($errors) && !is_array($errors)) {
                    printf("<p>%s</p>", $errors);
                }
            }
            echo "</div>";
        }
    }

}