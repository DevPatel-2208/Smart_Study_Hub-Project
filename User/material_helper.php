<?php
// includes/material_helper.php

// ये function SQL condition return करता है कि कौन सा material दिखाना है
function getVisibleMaterialCondition($user_id) {
    return "(sm.visible_to_user_id IS NULL OR sm.visible_to_user_id = $user_id)";
}
?>
