<?php
require_once 'config.php'; require_login();
$cart=$_SESSION['cart']??[]; if(!$cart) redirect('shop.php');
$ids=array_keys($cart);$ph=implode(',',array_fill(0,count($ids),'?'));$types=str_repeat('i',count($ids));
$stmt=$db->prepare("SELECT * FROM products WHERE id IN ($ph)");$stmt->bind_param($types,...$ids);$stmt->execute();$products=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$subtotal=0;$items=[];
foreach($products as $p){$qty=$cart[$p['id']];if($qty>$p['stock']){$qty=$p['stock'];$_SESSION['cart'][$p['id']]=$qty;} $line=$qty*$p['price'];$subtotal+=$line;$items[]=['p'=>$p,'qty'=>$qty,'line'=>$line];}
if(!$items) redirect('shop.php');

$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $db->begin_transaction();
        foreach($items as $item){
            $stmt=$db->prepare("SELECT stock FROM products WHERE id=? FOR UPDATE");$id=$item['p']['id'];$stmt->bind_param("i",$id);$stmt->execute();$fresh=$stmt->get_result()->fetch_assoc();
            if(!$fresh || $fresh['stock'] < $item['qty']) throw new Exception("Insufficient stock for ".$item['p']['name']);
        }
        $invoice='BGV-'.date('YmdHis').'-'.random_int(100,999);
        $uid=$_SESSION['user_id'];
        $stmt=$db->prepare("INSERT INTO orders(user_id,invoice_no,subtotal,total,status) VALUES(?,?,?,?, 'paid')");
        $stmt->bind_param("isdd",$uid,$invoice,$subtotal,$subtotal);$stmt->execute();$order_id=$db->insert_id;
        foreach($items as $item){
            $p=$item['p'];$stmt=$db->prepare("INSERT INTO order_items(order_id,product_id,product_name,price,quantity,total) VALUES(?,?,?,?,?,?)");
            $stmt->bind_param("iisdid",$order_id,$p['id'],$p['name'],$p['price'],$item['qty'],$item['line']);$stmt->execute();
            $stmt=$db->prepare("UPDATE products SET stock=stock-? WHERE id=?");$stmt->bind_param("ii",$item['qty'],$p['id']);$stmt->execute();
        }
        $db->commit(); $_SESSION['cart']=[]; redirect("invoice.php?id=".$order_id);
    }catch(Throwable $e){$db->rollback();$error=$e->getMessage();}
}
?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Checkout | <?= e(SITE_NAME) ?></title><link rel="stylesheet" href="assets/css/style.css"></head><body>
<?php include 'includes/header.php'; ?><section class="section"><div class="container"><h1>Checkout</h1>
<?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<div class="summary" style="margin:0"><h3>Order summary</h3><?php foreach($items as $item): ?><div class="summary-row"><span><?= e($item['p']['name']) ?> × <?= $item['qty'] ?></span><strong><?= money($item['line']) ?></strong></div><?php endforeach; ?><div class="summary-row summary-total"><span>Total</span><strong><?= money($subtotal) ?></strong></div><form method="post"><button class="btn" style="width:100%;margin-top:15px">Confirm Purchase</button></form></div>
</div></section><?php include 'includes/footer.php'; ?></body></html>