<?php
function get_user_points($user_id) {
    global $db;
    $query = "SELECT loyalty_points FROM users WHERE id = $1";
    $result = pg_query_params($db, $query, array($user_id));
    return pg_fetch_result($result, 0, 0);
}

function add_loyalty_points($user_id, $points) {
    global $db;
    $query = "UPDATE users SET loyalty_points = loyalty_points + $1 WHERE id = $2";
    return pg_query_params($db, $query, array($points, $user_id));
}

function get_user_tier($points) {
    if ($points < 1000) {
        return "Bronze";
    } elseif ($points < 5000) {
        return "Silver";
    } elseif ($points < 10000) {
        return "Gold";
    } else {
        return "Platinum";
    }
}

function calculate_bonus_multiplier($tier) {
    switch ($tier) {
        case "Bronze":
            return 1.0;
        case "Silver":
            return 1.1;
        case "Gold":
            return 1.2;
        case "Platinum":
            return 1.3;
        default:
            return 1.0;
    }
}

function redeem_points($user_id, $points_to_redeem) {
    global $db;
    $user_points = get_user_points($user_id);
    
    if ($user_points < $points_to_redeem) {
        return false;
    }
    
    $cash_value = $points_to_redeem / 100; // 100 points = $1
    
    $db->beginTransaction();
    
    try {
        $query = "UPDATE users SET loyalty_points = loyalty_points - $1, balance = balance + $2 WHERE id = $3";
        pg_query_params($db, $query, array($points_to_redeem, $cash_value, $user_id));
        
        record_transaction($user_id, $cash_value, 'loyalty_redemption');
        
        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollBack();
        return false;
    }
}