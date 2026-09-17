<?php
require_once 'config.php';
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['product_id'] ?? 0);

    if ($action === 'add') {
        $stmt=$db->prepare("SELECT id,stock FROM products WHERE id=?");
        $stmt->bind_param("i",$id);$stmt->execute();$p=$stmt->get_result()->fetch_assoc();
        if($p && $p['stock']>0) $_SESSION['cart'][$id]=min(($_SESSION['cart'][$id]??0)+1,(int)$p['stock']);
    } elseif ($action === 'update') {
        foreach(($_POST['qty'] ?? []) as $pid=>$qty){
            $pid=(int)$pid;$qty=max(0,(int)$qty);
            $stmt=$db->prepare("SELECT stock FROM products WHERE id=?");$stmt->bind_param("i",$pid);$stmt->execute();$p=$stmt->get_result()->fetch_assoc();
            if(!$p || $qty===0) unset($_SESSION['cart'][$pid]); else $_SESSION['cart'][$pid]=min($qty,(int)$p['stock']);
        }
    } elseif ($action === 'remove') {
        unset($_SESSION['cart'][$id]);
    }
    redirect('cart.php');
}
$cart=$_SESSION['cart'];$items=[];$subtotal=0;
if($cart){
    $ids=array_keys($cart);$placeholders=implode(',',array_fill(0,count($ids),'?'));
    $types=str_repeat('i',count($ids));
    $stmt=$db->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->bind_param($types,...$ids);$stmt->execute();
    $rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach($rows as $p){$qty=$cart[$p['id']];$line=$qty*$p['price'];$subtotal+=$line;$items[]=['p'=>$p,'qty'=>$qty,'line'=>$line];}
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Cart | <?= e(SITE_NAME) ?></title><link rel="stylesheet" href="assets/css/style.css"></head><body>
<?php include 'includes/header.php'; ?><section class="section"><div class="container"><h1>Your cart</h1>
<?php if(!$items): ?><p>Your cart is empty. <a href="shop.php" style="color:var(--primary)">Continue shopping.</a></p>
<?php else: ?><form method="post"><input type="hidden" name="action" value="update"><div class="table-wrap"><table class="cart-table"><tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th><th></th></tr>
<?php foreach($items as $item): $p=$item['p']; ?><tr><td><?= e($p['name']) ?></td><td><?= money($p['price']) ?></td><td><input class="form-control" style="width:80px" type="number" min="0" max="<?= (int)$p['stock'] ?>" name="qty[<?= (int)$p['id'] ?>]" value="<?= (int)$item['qty'] ?>"></td><td><?= money($item['line']) ?></td><td><button class="btn btn-danger btn-small" type="submit" formaction="cart.php" name="action" value="remove" formmethod="post">Remove</button><input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>"></td></tr><?php endforeach; ?></table></div>
<button class="btn" style="margin-top:15px">Update Cart</button></form>
<div class="summary"><div class="summary-row"><span>Subtotal</span><strong><?= money($subtotal) ?></strong></div><div class="summary-row summary-total"><span>Total</span><strong><?= money($subtotal) ?></strong></div><a class="btn" style="width:100%;text-align:center;margin-top:15px" href="checkout.php">Proceed to checkout</a></div>
<?php endif; ?></div></section><?php include 'includes/footer.php'; ?></body></html>