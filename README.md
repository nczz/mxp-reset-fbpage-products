### 清除粉絲頁商品工具

此工具使用前提是安裝 [Facebook for WooCommerce](https://tw.wordpress.org/plugins/facebook-for-woocommerce/) (v2.6.7以上版本) 並且 `授權粉絲頁與同步商品`。

由於 Facebook 此版同步外掛使用上還有一些同步問題，建議下架商品的操作方式為先把商品改為「`未發布`」狀態，然後等外掛同步 Facebook 後才將商品刪除。

如果這樣的順序搞錯，就有可能造成先前同步上去的商品無法透過同步管理，時間一長與商品量一大，就會造成更多延伸問題。

此外掛透過 Facebook API 直接刪除粉絲頁上架商品，再藉由原本 `Facebook for WooCommerce` 外掛同步功能將目前站上商品同步回去，如此作法較為乾淨，避免有同步無法處理的孤兒發生。