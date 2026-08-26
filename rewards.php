<?php
include_once("database.php");

$sql = "SELECT item_id, name, description, stock_left, image_id, cost FROM store ORDER BY name ASC";
$result = $conn->execute_query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rewards</title>

    <link rel="stylesheet" href="styles/rewards.css">
    <?php include("global.php"); ?>
</head>

<body>
    <?php include("header.php"); ?>

    <main class="content">

        <div class="page-title-bar">
            <h1>Rewards</h1>
        </div>

        <?php if ($result->num_rows > 0) : ?>
            <div class="rewards-container">
                <div class="rewards-grid">
                    <?php while ($row = $result->fetch_assoc()) : ?>
                        <?php
                        $image_path = get_image_path($row['image_id']);
                        $stock_class = ($row['stock_left'] <= 10) ? "info-row stock-low" : "info-row";
                        ?>
                        <div class="reward-card">
                            <img src="<?php echo htmlspecialchars($image_path); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
                            <div class="reward-card-info">
                                <div class="info-row info-name"><?php echo htmlspecialchars($row['name']); ?></div>
                                <div class="info-row info-desc"><?php echo htmlspecialchars($row['description']); ?></div>
                                <div class="info-row">Cost: <?php echo htmlspecialchars($row['cost']); ?> Points</div>
                                <div class="<?php echo $stock_class; ?>">Stock left: <?php echo htmlspecialchars($row['stock_left']); ?></div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php else : ?>
            <div class="empty-state">No rewards available right now.</div>
        <?php endif; ?>

    </main>

    <?php include("footer.php"); ?>
</body>

</html>