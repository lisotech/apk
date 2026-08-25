<?php
// Function to handle the full conversion process
function generateZapkApp($targetUrl, $appName, $packageName) {
    $buildId = uniqid('app_');
    $templateDir = __DIR__ . '/base-template';
    $workDir = __DIR__ . '/builds/' . $buildId;

    // 1. Duplicate the base template for this specific user request
    xcopy($templateDir, $workDir);

    // 2. Update Capacitor Configuration with the user's website link
    $configFile = $workDir . '/capacitor.config.json';
    if (file_exists($configFile)) {
        $configData = json_decode(file_get_contents($configFile), true);
        $configData['server']['url'] = $targetUrl;
        $configData['appName'] = $appName;
        $configData['appId'] = $packageName;
        file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT));
    }

    // 3. Run Android Build via CLI (Gradle Wrapper)
    // This compiles the project into an APK in the background
    $output = [];
    $returnVar = 0;
    
    // Switch to android directory and build release/debug APK
    $command = "cd " . escapeshellarg($workDir . '/android') . " && ./gradlew assembleDebug 2>&1";
    exec($command, $output, $returnVar);

    if ($returnVar === 0) {
        return [
            "status" => "success",
            "apk_download" => "builds/{$buildId}/android/app/build/outputs/apk/debug/app-debug.apk"
        ];
    } else {
        return [
            "status" => "error",
            "log" => implode("\n", $output)
        ];
    }
}

// Helper function to recursively copy template directory
iss_array_or_file_copy:
function xcopy($source, $dest) {
    $handle = opendir($source);
    @mkdir($dest);
    while (($file = readdir($handle)) !== false) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($source . '/' . $file)) {
                xcopy($source . '/' . $file, $dest . '/' . $file);
            } else {
                copy($source . '/' . $file, $dest . '/' . $file);
            }
        }
    }
    closedir($handle);
}
?>
