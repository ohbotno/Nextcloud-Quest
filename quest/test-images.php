<?php
/**
 * Test file to debug image path issues
 * Access this directly in your browser to see which method works
 */

// Different methods to generate image paths in Nextcloud
?>
<!DOCTYPE html>
<html>
<head>
    <title>Image Path Test</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            padding: 20px;
            background: #f5f5f5;
        }
        .test-container {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-method {
            margin: 15px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .test-method h3 {
            margin-top: 0;
            color: #333;
        }
        .test-method code {
            display: block;
            background: #f0f0f0;
            padding: 10px;
            margin: 10px 0;
            border-radius: 3px;
            font-size: 12px;
            overflow-x: auto;
        }
        .test-method img {
            max-width: 100px;
            max-height: 100px;
            border: 2px solid #0082c9;
            padding: 5px;
            background: white;
        }
        .status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
        .status.success { background: #46ba61; color: white; }
        .status.error { background: #e9322d; color: white; }
    </style>
</head>
<body>
    <h1>🔍 Nextcloud Quest - Image Path Testing</h1>
    
    <div class="test-container">
        <h2>Testing different methods to load images in Nextcloud</h2>
        <p>Looking for: <strong>app.svg</strong> and <strong>characters/base/avatar-128.png</strong></p>
        
        <?php
        // Method 1: Using OC::$server->getURLGenerator()
        if (class_exists('OC')) {
            ?>
            <div class="test-method">
                <h3>Method 1: OC::$server->getURLGenerator()->imagePath()</h3>
                <code>&lt;?php echo \OC::$server->getURLGenerator()->imagePath('quest', 'app.svg'); ?&gt;</code>
                <?php 
                try {
                    $path1 = \OC::$server->getURLGenerator()->imagePath('quest', 'app.svg');
                    $path2 = \OC::$server->getURLGenerator()->imagePath('quest', 'characters/base/avatar-128.png');
                    echo "<p>Generated paths:</p>";
                    echo "<ul>";
                    echo "<li>app.svg: <strong>$path1</strong></li>";
                    echo "<li>avatar: <strong>$path2</strong></li>";
                    echo "</ul>";
                    echo '<img src="' . $path1 . '" alt="App Icon" />';
                    echo '<img src="' . $path2 . '" alt="Avatar" />';
                    echo '<span class="status success">✓ Method available</span>';
                } catch (Exception $e) {
                    echo '<span class="status error">✗ Error: ' . $e->getMessage() . '</span>';
                }
                ?>
            </div>
            <?php
        }
        
        // Method 2: Using image_path function
        if (function_exists('image_path')) {
            ?>
            <div class="test-method">
                <h3>Method 2: image_path() function</h3>
                <code>&lt;?php echo image_path('quest', 'app.svg'); ?&gt;</code>
                <?php 
                try {
                    $path1 = image_path('quest', 'app.svg');
                    $path2 = image_path('quest', 'characters/base/avatar-128.png');
                    echo "<p>Generated paths:</p>";
                    echo "<ul>";
                    echo "<li>app.svg: <strong>$path1</strong></li>";
                    echo "<li>avatar: <strong>$path2</strong></li>";
                    echo "</ul>";
                    echo '<img src="' . $path1 . '" alt="App Icon" />';
                    echo '<img src="' . $path2 . '" alt="Avatar" />';
                    echo '<span class="status success">✓ Function available</span>';
                } catch (Exception $e) {
                    echo '<span class="status error">✗ Error: ' . $e->getMessage() . '</span>';
                }
                ?>
            </div>
            <?php
        } else {
            ?>
            <div class="test-method">
                <h3>Method 2: image_path() function</h3>
                <span class="status error">✗ Function not available</span>
            </div>
            <?php
        }
        
        // Method 3: Direct relative paths
        ?>
        <div class="test-method">
            <h3>Method 3: Direct relative paths</h3>
            <code>&lt;img src="/apps/quest/img/app.svg" /&gt;</code>
            <p>Generated paths:</p>
            <ul>
                <li>app.svg: <strong>/apps/quest/img/app.svg</strong></li>
                <li>avatar: <strong>/apps/quest/img/characters/base/avatar-128.png</strong></li>
            </ul>
            <img src="/apps/quest/img/app.svg" alt="App Icon" />
            <img src="/apps/quest/img/characters/base/avatar-128.png" alt="Avatar" />
            <span class="status success">✓ Always available (if files exist)</span>
        </div>
        
        <?php
        // Method 4: Using OC_Helper if available
        if (class_exists('OC_Helper')) {
            ?>
            <div class="test-method">
                <h3>Method 4: OC_Helper::imagePath()</h3>
                <code>&lt;?php echo \OC_Helper::imagePath('quest', 'app.svg'); ?&gt;</code>
                <?php 
                try {
                    $path1 = \OC_Helper::imagePath('quest', 'app.svg');
                    $path2 = \OC_Helper::imagePath('quest', 'characters/base/avatar-128.png');
                    echo "<p>Generated paths:</p>";
                    echo "<ul>";
                    echo "<li>app.svg: <strong>$path1</strong></li>";
                    echo "<li>avatar: <strong>$path2</strong></li>";
                    echo "</ul>";
                    echo '<img src="' . $path1 . '" alt="App Icon" />';
                    echo '<img src="' . $path2 . '" alt="Avatar" />';
                    echo '<span class="status success">✓ Class available</span>';
                } catch (Exception $e) {
                    echo '<span class="status error">✗ Error: ' . $e->getMessage() . '</span>';
                }
                ?>
            </div>
            <?php
        }
        ?>
        
        <div class="test-method">
            <h3>File System Check</h3>
            <?php
            $imgPath = __DIR__ . '/img/';
            echo "<p>Checking for files in: <strong>$imgPath</strong></p>";
            echo "<ul>";
            
            if (file_exists($imgPath . 'app.svg')) {
                echo '<li>✓ app.svg exists</li>';
            } else {
                echo '<li>✗ app.svg NOT found</li>';
            }
            
            if (file_exists($imgPath . 'characters/base/avatar-128.png')) {
                echo '<li>✓ characters/base/avatar-128.png exists</li>';
            } else {
                echo '<li>✗ characters/base/avatar-128.png NOT found</li>';
            }
            
            echo "</ul>";
            
            // List all files in img directory
            if (is_dir($imgPath)) {
                echo "<p>Files in img directory:</p>";
                echo "<pre>";
                $files = scandir($imgPath);
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..') {
                        echo "- $file\n";
                        if (is_dir($imgPath . $file)) {
                            $subfiles = scandir($imgPath . $file);
                            foreach ($subfiles as $subfile) {
                                if ($subfile != '.' && $subfile != '..') {
                                    echo "  - $file/$subfile\n";
                                    if (is_dir($imgPath . $file . '/' . $subfile)) {
                                        $subsubfiles = scandir($imgPath . $file . '/' . $subfile);
                                        foreach ($subsubfiles as $subsubfile) {
                                            if ($subsubfile != '.' && $subsubfile != '..') {
                                                echo "    - $file/$subfile/$subsubfile\n";
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                echo "</pre>";
            }
            ?>
        </div>
    </div>
    
    <div class="test-container">
        <h2>✅ Recommended Solution</h2>
        <p>Based on the tests above, use the method that shows images correctly. The most reliable methods are usually:</p>
        <ol>
            <li><strong>For templates:</strong> <code>&lt;?php echo \OC::$server->getURLGenerator()->imagePath('quest', 'filename.ext'); ?&gt;</code></li>
            <li><strong>Fallback:</strong> <code>/apps/quest/img/filename.ext</code> (direct path)</li>
        </ol>
    </div>
</body>
</html>