<?php

class Product extends Superobj
{

    //var $Crumbs_local;
    var $crumbs = array();
    protected $post_arr = array();
    protected $file_arr = array();
    protected $del_arr;
    // protected $limit = 2; //上傳檔案大小
    protected $sort_where;
    protected $tbname = PRODUCT;
    protected $tbname_img = PRODUCT_IMG;
    var $sdir = PD_Image;
    var $back = './product.php?p=';
    public $s_size = array("m" => array("w" => 500, "h" => 370), "s" => array("w" => 225, "h" => 150), "ss" => array("w" => 75, "h" => 50));
    var $is_image = false;
    var $list_this;
    var $detail_this;
    var $this_Page = this_Page;
    var $detail_id; //編輯細節ID
    var $is_sort = false;
    var $sort_arr = array();
    var $status_arr = array(1 => "上架", 0 => "下架");

    ####################################################################################
    function __construct($debug = Debug)
    {
        $this->post_arr = (is_array($_POST)) ? $_POST : "";
        $this->file_arr = (is_array($_FILES)) ? $_FILES : "";
        $this->sort_arr = (isset($_POST['sort'])) ? $_POST['sort'] : "";
        $this->del_arr = (isset($_REQUEST['delid'])) ? $_REQUEST['delid'] : "";
        $this->detail_id = (is_numeric($_GET['id'])) ? $_GET['id'] : "";
        // $this->set_sort_arr();

        parent::__construct($debug);

        if (trim($this->tbname) != '')
            $this->set_field($this->tbname);
    }

    function get_dir()
    {
        return $this->sdir;
    }

    function get_crumb_html()
    {
        $crumb = '<ul class="crumb">
                    <li><a href="index.php" class="home">&nbsp;</a></li>
                    <li><a href="product_bcatalog.php">產品管理</a></li>
                    <li><span>產品列表</span></li>
                </ul>';

        return $crumb;
    }

    function get_toolbar_html()
    {
        $toolbar = '<ul class="group">
                        <li><a href="product_detail.php" class="file-add">新增商品</a></li>
                    </ul>
                    <ul class="group">
                        <li><a href="#" onclick="return del();" class="file-delete">批次刪除</a></li>
                        <li><a href="#" onclick="return sale(1);" class="on">批次上架</a></li>
                        <li><a href="#" onclick="return sale(0);" class="off">批次下架</a></li>
                    </ul>';

        return $toolbar;
    }

    function get_catalog()
    {
        $p = $_POST['p'];
        $obj = new Catalog;
        $ret = $obj->get_all_for_product($p);
        $output = '<option value="' . $p . '">請選子分類（可不選）</option>';
        foreach ($ret as $v)
            $output.='<option value="' . $v['id'] . '">' . $v['title'] . '</option>';

        echo $output;
        exit;
    }

    function get_detail_crumb_html()
    {
        $crumb = '<ul class="crumb">
                    <li><a href="index.php" class="home">&nbsp;</a></li>
                    <li><a href="product_bcatalog.php">產品管理</a></li>
                    <li><span>產品列表</span></li>
                </ul>';

        return $crumb;
    }

    function get_all($p = '', $s = '')
    {
        $s = (!is_numeric($s)) ? $_GET['s'] : $s;
        $p = (!is_numeric($p)) ? $_GET['p'] : $p;

        if (is_numeric($s) && $s != '')
            $wheres = " AND a.`status` = " . $s;

        if (is_numeric($p) && $p != '')
            $parent = " AND a.`parent` = " . $p;

        $this->list_this = "SELECT a.`sequ`, a.`id`, a.`title`, a.`parent`, a.`status`, b.`path`
                            FROM " . $this->tbname . " a
                            LEFT JOIN " . $this->tbname_img . " b ON a.`id` = b.`parent` AND b.`master` = 1
                            WHERE  1 " . $parent . " " . $wheres . " /* AND b.`master` = 1 */
                            ORDER BY a.`sequ` ASC, a.`dates` DESC, a.`status` DESC";
        // exit($this->list_this);
        return parent::get_list($this->list_this);
    }

    function get_detail($pk = '')
    { //列出單筆細節
        $pk = (is_numeric($pk)) ? $pk : $this->detail_id;

        if (trim($pk) != '')
            $this->detail_this = "SELECT * FROM " . $this->tbname . " WHERE " . $this->PK . "=" . $pk;

        return parent::get_list($this->detail_this, 1);
    }

    function get_pre_img($path = "")
    {
        if (is_file($this->get_dir() . $path))
            return $this->get_dir() . "s_" . $path;
        else
            return "images/logo.png";
    }

    function get_img_detail($p = "")
    {
        $p = (is_numeric($_GET['p'])) ? $_GET['p'] : $p;

        $sql = "SELECT * FROM " . $this->tbname_img . " WHERE `parent` = " . (int) $p . " ORDER BY `master` DESC, `id` ASC";
        return parent::get_list($sql);
    }

    function get_img_master($p)
    {
        if (!is_numeric($p))
            return false;

        $sql = "SELECT `id` FROM " . $this->tbname_img . " WHERE `parent` = " . (int) $p . " AND `master` = 1";
        return parent::get_list($sql, 1);
    }

    /* for 檔案下載列表 */
    function get_product($p = '')
    {
        if ($_POST['p'] <= 0 && $p <= 0)
            return false;

        if (is_numeric($_POST['p']) && $p == '')
            $p = $_POST['p'];

        $obj = new Catalog;
        $ret = $obj->get_all_for_product($p);
        $parent_arr = array();
        $parent_arr[0] = $p;

        if (!$_POST['p'])
            $ret_arr = array();

        foreach ($ret as $v)
            $parent_arr[] = $v['id'];

        $output = '<option value="0" selected="selected">請選擇產品</option>';

        foreach ($parent_arr as $v)
        {
            $ret2 = self::get_all($v);
            foreach ($ret2 as $v2)
            {
                $vr = $v2['id'];
                $tr = $v2['title'];
                $ret_arr[] = $v2;
                $status = $v2['status'] != '1' ? '(下架) ' : '';
                $output .= '<option value="' . $v2['id'] . '">' . $status . $v2['title'] . '</option>';
            }
        }

        /* for下拉二層選單 */
        if (!$_POST['p'])
            return $ret_arr;
        echo $output;

        exit;
    }

    #############################################################################
    function get_img_detail_front($p = "")
    {
        $p = (is_numeric($_GET['id'])) ? $_GET['id'] : $p;

        $sql = "SELECT * FROM " . $this->tbname_img . " WHERE `parent` = " . (int) $p . " ORDER BY `master` DESC, `id` ASC";
        return parent::get_list($sql);
    }

    function get_front()
    {
        $this->list_this = "SELECT * FROM " . $this->tbname . " WHERE sale='1' ORDER BY dates desc limit 5";
        return parent::get_list($this->list_this);
    }

    function get_all_front($p = '', $l = '')
    {
        // $s = (!is_numeric($s)) ? $_GET['s'] : $s;
        $p = (!is_numeric($p)) ? $_GET['p'] : $p;

        // if (is_numeric($s) && $s != '')
        // $wheres = " AND a.`status` = " . $s;

        if ((is_numeric($p) && $p != '')/*  ||  */)
        {
            $parent = " AND a.`parent` = " . $p;
            $brief = " , a.`brief` ";
        }

        if ($this->this_Page == 'products.php')
            $brief = " , a.`brief` ";

        if (is_numeric($l) && $l > 0)
            $limit = " LIMIT 0, " . $l;

        $this->list_this = "SELECT a.`id`, a.`title`, a.`parent`, a.`status`, b.`path` " . $brief
                . " FROM " . $this->tbname . " a
                            LEFT JOIN " . $this->tbname_img . " b ON a.`id` = b.`parent` AND b.`master` = 1
                            WHERE  1 " . $parent . " " . $wheres . " /* AND b.`master` = 1 */ AND a.`status` = 1
                            ORDER BY a.`sequ` ASC, a.`dates` DESC";
        // exit($this->list_this);
        return parent::get_list($this->list_this . $limit);
    }

    function get_new_item_front($l = 29)
    {
        $brief = " , a.`brief` ";
        $this->list_this = "SELECT a.`id`, a.`title`, a.`parent`, a.`status`, b.`path` " . $brief
                . " FROM " . $this->tbname . " a
                LEFT JOIN " . $this->tbname_img . " b ON a.`id` = b.`parent` AND b.`master` = 1
                WHERE  1 " . $parent . " " . $wheres . " /* AND b.`master` = 1 */ AND a.`status` = 1
                ORDER BY a.`dates` DESC";

        // if (is_numeric($l) && $l > 0)
        // $limit = " LIMIT 0, " . $l;

        $ret = parent::get_list($this->list_this . $limit);
        // exit($this->list_this);
        $Catalog = new Catalog;
        $nosale = 0;
        $new_ret = array();
        foreach ($ret as $k => $v)
        {

            $bcatalog = $Catalog->get_parent_for_product($v['parent']) == 0 ? $v['parent'] : $Catalog->get_parent_for_product($v['parent']);

            $bstatus = $Catalog->get_detail_front($bcatalog);
            $pstatus = $Catalog->get_detail_front($v['parent']);
            if ($bstatus['status'] != '1' || $pstatus['status'] != '1')
            {
                // unset($ret[$k]);
                $nosale++;
                continue;
            }

            if (($k - $nosale) > $l)
            {
                continue;
            }

            array_push($new_ret, $v);
        }

        return $new_ret;
    }

    function get_detail_front($pk = '')
    { //列出單筆細節
        $pk = (is_numeric($pk)) ? $pk : $this->detail_id;

        if (trim($pk) != '')
            $this->detail_this = "SELECT * FROM " . $this->tbname . " WHERE `status` = 1 AND " . $this->PK . "=" . $pk;

        return parent::get_list($this->detail_this, 1);
    }

    function get_status($v)
    {
        return $this->status_arr[$v];
    }

    ############################################################################
    function renew()
    {
        /* for images */
        if ($this->tbname == add_field_quotes($this->tbname_img))
        {
            /* 手動設定封面 */
            if (is_numeric($_POST['master']))
            {
                $ret = self::get_img_master($_POST['parent']);
                $arr = array('id' => $ret['id'], 'master' => 0);
                parent::renew($arr);
                $arr = array('id' => $_POST['master'], 'master' => 1);
                parent::renew($arr);
                return;
            }

            self::set_back("product_detail_photo.php?p=" . $_POST['parent']);
            foreach ($_POST['path'] as $v)
            {
                $arr = array('path' => $v, 'parent' => $_POST['parent']);
                parent::renew($arr);
            }

            self::set_master();

            return;
        }

        /* for 置頂 */
        if ($this->post_arr['status'] == '1')
        {
            $this->post_arr['dates'] = date("Y-m-d H:i:s");
        }

        parent::renew($this->post_arr, $this->file_arr, $this->sdir, $this->s_size);
        $returnID = (!is_numeric($_POST['id'])) ? $this->get_lastID() : $_POST['id'];
        // self::set_back("product_detail.php?p=" . $_POST['parent']);
        self::set_back("product_detail.php?id=" . $returnID);
        // echo $_POST['id'].'::'.$this->get_lastID();
        // exit;
    }

    function killu()
    {
        /* for images */
        if ($this->tbname == add_field_quotes($this->tbname_img))
        {
            parent::killu();

            self::set_master();
            ob_clean();

            echo 'ok';
            exit;
        }

        if ($this->post_arr['parent'])
        {
            self::set_back("product_catalog.php?bc=" . $_POST['parent']);
        }
        return parent::killu($this->del_arr, $this->is_image, $this->sdir);
    }

    function sale()
    {
        if ($this->post_arr['parent'])
        {
            self::set_back("product.php?p=" . $_POST['parent'] . "&s=" . $_POST['status']);
        }

        foreach ($this->del_arr as $v)
        {
            $arr = array();
            $arr['id'] = $v;
            $arr['status'] = $_POST['status'];
            if ($_POST['status'] == '1')
            {
                $arr['dates'] = date("Y-m-d H:i:s");
            }
            parent::renew($arr);
        }
        return;
    }

    function set_back($page)
    {
        $this->back = $page;
    }

    function is_sort()
    {
        return $this->is_sort;
    }

    function set_sort_arr()
    {
        $this->sort_arr = $_POST['sort_arr'];
    }

    function get_sort_arr()
    {
        if ($this->post_arr['parent'] > 0)
        {
            self::set_back("product.php?p=" . $_POST['parent']);
        }

        return $this->sort_arr;
    }

    function set_sort_where($parent)
    {
        $this->sort_where = " AND `parent` = " . (int) $parent;
    }

    function get_s_size()
    {
        return $this->s_size;
    }

    function set_master()
    {
        /* 自動設定封面 */
        if (!$ret = self::get_img_master($_POST['parent']))
        {
            if ($ret = self::get_img_detail($_POST['parent']))
            {
                $arr = array('id' => $ret[0]['id'], 'master' => 1);
                parent::renew($arr);
            }
        }
        return;
    }

}