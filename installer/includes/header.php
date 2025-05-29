<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Boilerplate Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 mt-5">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Laravel Boilerplate Installer</h4>
                    </div>
                    <div class="card-body">
                        <div class="progress mb-4">
                            <?php
                            $currentStep = $this->getCurrentStep();
                            $totalSteps = count(Installer\Config::STEPS);
                            $stepPercentage = (($currentStep - 1) / ($totalSteps > 1 ? $totalSteps -1 : 1)) * 100;
                            ?>
                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $stepPercentage; ?>%" 
                                aria-valuenow="<?php echo $stepPercentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo round($stepPercentage); ?>%
                            </div>
                        </div>
                        
                        <ul class="nav nav-pills nav-fill mb-4">
                            <?php foreach (Installer\Config::STEPS as $index => $stepData): ?>
                                <li class="nav-item">
                                    <?php 
                                    $class = '';
                                    if ($index < $currentStep) {
                                        $class = 'bg-success text-white';
                                    } elseif ($index == $currentStep) {
                                        $class = 'bg-primary text-white';
                                    } else {
                                        $class = 'bg-light';
                                    }
                                    ?>
                                    <span class="nav-link <?php echo $class; ?>">
                                        <?php echo $index; ?>. <?php echo $stepData['name']; ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <!-- The step content will be included directly here by Controller::renderPage() -->
