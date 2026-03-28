<?php

use app\models\settings\Privileges;
use app\models\db\{Consultation, User};

$user = User::getCurrentUser();
$consultation = Consultation::getCurrent();
$iAmAdmin = ($user && $user->hasPrivilege($consultation, Privileges::PRIVILEGE_VOTINGS, null));

$CONSTANTS = include(__DIR__ . DIRECTORY_SEPARATOR . '_constants.php');

?>
<script type="module">
    import { VotingBlock } from "/js/modules/frontend/VotingBlock.js";
    new VotingBlock(
        document.querySelector(".currentVotingWidget"),
        <?= json_encode($CONSTANTS) ?>,
        <?= json_encode( include(__DIR__ . '/../../messages/en/voting.php') ) ?>
    );
</script>
