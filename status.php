<?php
$app_id = $_GET['application_id'] ?? "";

$steps = ["Pending", "In Review", "Approved"];
$random_step = rand(0, 2);

// Random April/May 2026 date
$month = rand(4, 5);
$day = rand(1, 28);
$date = sprintf("%02d/%02d/2026", $day, $month);
?>
<!DOCTYPE html>
<html>
<head>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex justify-center items-center h-screen">

<div class="bg-white shadow-xl rounded-lg p-8 w-[500px]">
    <h2 class="text-xl font-bold text-gray-800 mb-3 text-center">Driving Licence Application Status</h2>

    <p class="text-gray-700 font-medium mb-1">Application Number: 
        <span class="text-blue-600"><?= htmlspecialchars($app_id) ?></span>
    </p>

    <p class="text-gray-700 font-medium mb-4">Updated On: <span class="text-green-600"><?= $date ?></span></p>

    <!-- Status Bar -->
    <div class="flex items-center justify-between w-full mt-4">
        <?php foreach($steps as $index => $label): ?>
            <div class="flex flex-col items-center">
                <div class="w-10 h-10 flex items-center justify-center rounded-full 
                    <?php if($index <= $random_step) echo 'bg-blue-600 text-white'; else echo 'bg-gray-300 text-gray-600'; ?>">
                    <?= $index+1 ?>
                </div>
                <p class="mt-1 text-sm font-medium 
                    <?php if($index <= $random_step) echo 'text-blue-700'; else echo 'text-gray-500'; ?>">
                    <?= $label ?>
                </p>
            </div>
            <?php if($index < count($steps)-1): ?>
            <div class="flex-1 h-1 mx-2 
                <?php if($index < $random_step) echo 'bg-blue-600'; else echo 'bg-gray-300'; ?>">
            </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>