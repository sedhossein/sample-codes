<?php

namespace app\models;

// *shh*   Reference For Redis : https://github.com/phpredis/phpredis#hmset

use Redis;
use Yii;
use yii\helpers\FunctionsHelper;
use yii\redis\ActiveRecord;
use yii\web\Request;

/**
 * Class Visit
 * @package app\models
 */
class Visit extends ActiveRecord
{
    /**
     * @var
     */
    public $redis;


//    /**
//     * Visit constructor.
//     * @param string $password
//     */
//    public function __construct($password = "")
//    {
//        $this->redis = self::connect_redis($password);
//    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'eshop_visits';//  eshop_settings
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['product_type', 'product_id', 'created_at', 'visit', 'details'], 'required'],
            [['product_id', 'visit'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'visit_id' => 'Visit_id',
            'agent' => 'Agent',
            'visit_type' => 'Visit_type',
            'ip' => 'Ip',
            'visited_at' => 'Visited_at',
        ];
    }

// Description OF Visit :

//     *shh*  we have three hash table in our redis
// 1. `observes` Hash Table is Our Source, And We Read The Visit Counts From Here. it Save Number Of Viewed For Each Product
// And For Getting The Visit Statistics Or Count In Site, We Use This The Information Of This Table
// 2. `hash_table` For Save All Products and It Keys Name have Type:ID pattern , and It Values Can Be Moshaahede Counts of `Type:ID` Table.
// But At Now It Is Not.
// 3. Type:ID Tables That They Reset And Clean Every Day, And They Stores The Today Visits Count.  The Key Of Each main-hash-table Keys, Point To
//  One Product-hash-table with Type:ID Pattern Name


    /**
     *  If The Visit Was Unique In Last 24H, Calculate It And Save It To Redis
     * @param string $visit_type
     * @param int $visit_id
     * @return int
     */
    public static function set($visit_type = 'non', $visit_id = 0) //$visit_type should be a type in  the 'menu' table
    {

        $redis = self::connect_redis();

        $request = Yii::$app->request;
        $_ip = $request->getUserIP();
        $_user_agent = $request->getUserAgent();

        if (self::empty_object($redis)) { //($supplier_id, $result, $path, $description, $ip, $user_agent)
            FunctionsHelper::insert_log(-1, 'ناموفق','redis/set', 'در اتصال به ردیس دچار مشکل هستیم.به هنگام ثبت  بازدید در ردیس خطایی رخ داده.',$_ip,$_user_agent);
            return -1; // خطای اتصال به ردیس
        }

        $hash_table_product_key = self::make_product_key($visit_type, $visit_id); // pattern of keys => `Type:ID`
        $request = Yii::$app->request;


        $result = self::save_visit($redis, $hash_table_product_key, $request);
        return $result;
    }


    /**
     * Hash Table Have The All Products That They Has At Last One Seen
     * @param Redis $redis
     * @param $hash_table_product_key
     * @param Request $request
     * @param string $main_hash_table_name
     * @return int
     */
    protected static function save_visit(Redis $redis, $hash_table_product_key, Request $request, $main_hash_table_name = "hash_table")
    {
		
		$date = new \DateTime("now", new \DateTimeZone('Asia/Tehran') );// set time zone for get current hour
		$time =  (int)$date->format('H');// A Hour

        $user_id = Yii::$app->supplier->identity->id ?: "0";
        $_key = $time . '@' . $request->getUserIP() . $request->getUserAgent() . '@' . $user_id .''; // this key($_key) is Key Name for `Time:Type:ID` hash tables || They Are Unique
        $exist = $redis->hExists($main_hash_table_name, $hash_table_product_key); /*  BOOLEAN */

        // Make New Filed in Main HashTable
        if (!$exist)
            $redis->hSet($main_hash_table_name, $hash_table_product_key, '0');

//      product_hash_table_name ===> $hash_table_product_key; && $value field ($time) its not important
        $result = $redis->hSet($hash_table_product_key, $_key, $time);
        
        return $result;
    }


    /**
     * Make A Hash Table For One Product That Not Exist Now In Main Hash Table
     * @param Redis $redis
     * @param $key
     * @param $hash_table_name
     * @return int
     */
    protected static function make_product_visits_hash_table(Redis $redis, $key, $hash_table_name) // the  Key of Main Hash_table name, set on `Time:Type:ID`
    {
        return $redis->hSet($hash_table_name, $key, '0'); // The Value Fields (now : '0') is not Important
    }


    /**
     * Connect To The Redis
     * @param string $password
     * @return Redis
     */
    private static function connect_redis($password = "D0rh@t0")
    {
        $redis = new Redis();
        $redis->pconnect('127.0.0.1', 6379);

        if (!empty($password)) $redis->auth($password);

        return $redis;
    }


    /**
     * Make Key For Hash_Table
     * @param $visit_type
     * @param $visit_id
     * @return string
     */
    private static function make_product_key($visit_type, $visit_id) // (product type,product id)
    {
        $key = $visit_type . ':' . $visit_id . ""; //hash table key name "user:1"
        return $key;
    }


    /**
     * Get Product Visits
     * @param $type
     * @param $id
     * @param string $table_name
     * @return int|string
     */
    protected static function get_visit_statistic($type, $id, $table_name)
    {
        $redis = self::connect_redis();

        if (self::empty_object($redis))
        {
            // log
            $request = Yii::$app->request;
            $_ip = $request->getUserIP();
            $_user_agent = $request->getUserAgent();
            // set log
            FunctionsHelper::insert_log(-1, 'ناموفق','redis/get', 'Connection To Redis Unsuccessfully ',$_ip,$_user_agent,'get_visit_statistic => '.'در اتصال به ردیس دچار مشکل هستیم.به هنگام ثبت  بازدید در ردیس خطایی رخ داده.');
            $count = self::get_visit_from_mysql($type, $id);

            return $count;
        }

        $key = self::make_product_key($type, $id);
        $product_visit_hash_table_name = $key;

        $old_visits = (int)$redis->hGet($table_name, $key);
        $today_visits = (int)$redis->hLen($product_visit_hash_table_name);

        $count = $old_visits + $today_visits;

        return empty($count) ? 0 : $count;
    }


    /**
     *  Return The Visit Count Of A Product
     * @param $type
     * @param $id
     * @return false|int|null|string
     */
    public static function get_visit_from_mysql($type, $id)///("SELECT sum('visit') FROM `visits` WHERE 'product_type' = '$type' AND 'product_id' = '$id'")
    {
        $visits = (new \yii\db\Query())
            ->select('SUM(visit)')
            ->from('visits')
            ->where(['product_type' => $type, 'product_id' => $id])
            ->scalar();

        // log
        $request = Yii::$app->request;
        $_ip = $request->getUserIP();
        $_user_agent = $request->getUserAgent();
        $_user_id = (Yii::$app->supplier->identity->id) ?: 0;
        // set log
        FunctionsHelper::insert_log($_user_id, 'موفق', '/redis/get_visit_from_mysql', 'Get Visits Count From Mysql', $_ip, $_user_agent,'probably We Have Error In Connect Too Redis');

        return empty($visits) ? 0 : $visits;

    }


    /**
     *  Return The Visit Count Of Product
     *  == self::get_visit_statistic()
     *  Just For Easy Usage
     * @param $type
     * @param $id
     * @param string $table_name
     * @return int|string
     */
    public static function get($type, $id, $table_name = 'observes')
    {
        return self::get_visit_statistic($type, $id, $table_name);
    }


    /**
     *  Return Just Today|Daily Visits
     * @param $type
     * @param $id
     * @param string $table_name
     * @return int
     */
    public static function get_today_visits($type, $id, $table_name = 'observes')
    {
        $redis = self::connect_redis();

        if (self::empty_object($redis))
        {
            // log
            $request = Yii::$app->request;
            $_ip = $request->getUserIP();
            $_user_agent = $request->getUserAgent();

            FunctionsHelper::insert_log(-1, 'ناموفق','redis/get', 'Connection To Redis Unsuccessfully ',$_ip,$_user_agent,'get_today_visits => '.'در اتصال به ردیس دچار مشکل هستیم.به هنگام ثبت  بازدید در ردیس خطایی رخ داده.');
            $count = self::get_visit_from_mysql($type, $id);

            return $count; //خطای اتصال به سرور
        }

        $key = self::make_product_key($type, $id);
        $product_visit_hash_table_name = $key;

        $today_visits = (int)$redis->hLen($product_visit_hash_table_name);

        return empty($today_visits) ? 0 : $today_visits;
    }


    /**
     *  Return Visit Statistic Except Today Ones.
     * @param $type
     * @param $id
     * @param $table_name
     * @return int
     */
    public static function get_old_visits($type, $id, $table_name = 'observes')
    {
        $redis = self::connect_redis();

        if (self::empty_object($redis)) {
            // log
            $request = Yii::$app->request;
            $_ip = $request->getUserIP();
            $_user_agent = $request->getUserAgent();

            FunctionsHelper::insert_log(-1, 'ناموفق','redis/get', 'Connection To Redis Unsuccessfully ',$_ip,$_user_agent,'get_old_visits => '.'در اتصال به ردیس دچار مشکل هستیم.به هنگام ثبت  بازدید در ردیس خطایی رخ داده.');
            $count = self::get_visit_from_mysql($type, $id);

            return $count; //خطای اتصال به سرور
        }

        $key = self::make_product_key($type, $id);

        $old_visits = (int)$redis->hGet($table_name, $key);

        return empty($old_visits) ? 0 : $old_visits;
    }


    /**
     * This Func Is For, Clean Today Visits From Sub-Hash-Tables , and Save Them To `Observes` Hash-Table and Mysql
     * @return bool|int
     * @throws \ErrorException
     */
    public static function update_redis()
    {
        $redis = self::connect_redis();

        // log
        $request = Yii::$app->request;
        $_ip = $request->getUserIP();
        $_user_agent = $request->getUserAgent();

        if (self::empty_object($redis)) {
            FunctionsHelper::insert_log(-1, 'ناموفق','redis/get', 'Connection To Redis Unsuccessfully ',$_ip,$_user_agent,'update_redis => '.'در اتصال به ردیس دچار مشکل هستیم.به هنگام ثبت  بازدید در ردیس خطایی رخ داده.');
            return -1;// todo : alert to admin
        }

        $main_hash_table_keys = $redis->hGetAll('hash_table');

        try {
            $sql = self::make_sql_code_and_update_redis($redis, $main_hash_table_keys);

            if (!$sql) // nothing find to update
                return true;

            self::save_to_mysql($sql);

            //if was seccussfully,
            self::delete_daily_tables($redis, $main_hash_table_keys);

        } catch (\ErrorException $errorException) {
            FunctionsHelper::insert_log(-1, 'ناموفق','redis/update', 'در اتصال به ردیس دچار مشکل هستیم.به هنگام ثبت  بازدید در ردیس خطایی رخ داده.',$_ip,$_user_agent);
            throw $errorException;
        }
        return true;
    }


    /**
     * return id from key string
     * @param $key
     * @return mixed
     */
    private static function explode_id($key)
    {
        return explode(':', $key)[1];
    }


    /**
     * Query to Update Fields
     * @param $sql
     */
    protected static function save_to_mysql($sql)
    {
        // todo : handle sql errors !
        $connection = \Yii::$app->db2;
        $connection->createCommand($sql)->execute();
    }


    /**
     * Check The Object, If Its Empty Return True(1), Otherwise Return False(0)
     * @param $obj
     * @return int
     */
    protected static function empty_object($obj)
    {
        foreach ($obj AS $prop) {
            return 0;
        }
        return 1;
    }


    /**
     * Delete Table
     * @param $table_name
     * @return int
     */
    public static function clear_hash_table($table_name)
    {
        $redis = self::connect_redis();

        // log
        $request = Yii::$app->request;
        $_ip = $request->getUserIP();
        $_user_agent = $request->getUserAgent();

        if (self::empty_object($redis)) {
            FunctionsHelper::insert_log(-1, 'ناموفق','redis/get', 'Connection To Redis Unsuccessfully ',$_ip,$_user_agent,'clear_hash_table => '.'در اتصال به ردیس دچار مشکل هستیم.به هنگام ثبت  بازدید در ردیس خطایی رخ داده.');
            return -1;
        }

        $redis->delete($table_name);
    }


    /**
     * Delete Sub-HashTables (table name : `Type:ID`)
     * @param Redis $redis
     * @param $today_visits_keys
     */
    private static function delete_daily_tables(Redis $redis, $today_visits_keys)
    {
        foreach ($today_visits_keys as $main_hash_table_key => $product_visit_value) {
            $redis->delete($main_hash_table_key); //$main_hash_table_key == Type:ID Table
        }
    }


    /**
     *  This Function Make SQL code( Provide And Make Standard DB Fields) And Update `Observes` hash-table
     *  ( Add And Increment The New Visits To  Old Ones For Each Product )
     * @param Redis $redis
     * @param $main_hash_table_keys
     * @param Enter|string $table
     * @return string
     */
    protected static function make_sql_code_and_update_redis(Redis $redis, $main_hash_table_keys, $table = 'visits')
    {
        $sql = "INSERT INTO `eshop_visits` ( `product_type`,`product_id`,`visit`,`details`,`created_at`)  VALUES  ";

        // $_key == Time:Type:ID
        // $product_visit_value Is moshaahede! Seens Count .
        $index = 0;
        $records = 0;
        //in each cycle loop, we create the code of one record of visit statistic for each product
        foreach ($main_hash_table_keys as $product_visit_key => $product_visit_value) {//$product_visit_value is  moshaade and now is `0`

            $keys = $redis->hKeys($product_visit_key);// now we get the keys of 'Type:ID'(product_visit_key) table 

            if (empty($keys)) {
                ++$index;
                continue;
            }// nothing to update in this sub-hash-table

            $records++;// new record will be make


            // for Calculate The Moshaahede Count , Pass The `$product_visit_value` by Reference To Current Function
            $increment = self::update_observes_hash_table($redis, $product_visit_key); // it return


            // GET FIELDS OF RECORDS VALUES
            $detail = self::get_more_info_in_visits($redis, $keys, $product_visit_key); //serialazed array
            $product_id = (int)self::explode_id($product_visit_key);
            $product_type = self::explode_type($product_visit_key);
            $time = time();
            $today_visits = (int)$redis->hLen(self::make_product_key($product_type, $product_id));


            $sql .= " ('" . $product_type . "','" . $product_id . "','" . $today_visits . "','" . $detail . "','" . $time . "')";

            if (!self::is_last_valid_key($redis, $main_hash_table_keys, ++$index))
                $sql .= ",";

        }

        if ($records == 0)
            $sql = '';


        return $sql;
    }


    /**
     *  Save Data to `Observe` from `hash_table`(main product tables that contain Moshaahede and Today visit Counts)
     *  And Return Number of Last Statistic Of Visit
     * @param Redis $redis
     * @param $main_hash_table_key
     * @return int
     */
    protected static function update_observes_hash_table(Redis $redis, $main_hash_table_key)//  for Calculate The Moshaahede Count Get `&$product_visit_value` in function inputs
    {
        // each hash_table key($main_hash_table_key) is set on the another table name(Type:ID) that has details of visits for each product in current day
//        $product_visit_table = $redis->hGetAll($main_hash_table_key);

        // Observes is our Visit Database
        $today_product_visit_count = $redis->hLen($main_hash_table_key);
        $increment = $redis->hIncrBy('observes', $main_hash_table_key, $today_product_visit_count);// if some field is not exist, this make it; if exist now,

        return $increment;
    }

    /**
     *  Tokenize the String With ':'
     * @param $key
     * @return mixed
     */
    protected static function explode_type($key)
    {
        return explode(':', $key)[0];
    }


    /**
     *  Make Details Fields For mysql
     * @param Redis $redis
     * @param $product_keys
     * @return string
     */
    protected static function get_more_info_in_visits(Redis $redis, $product_keys, $product_visit_key = 'father_table')
    {
        $cpy_product_keys = $product_keys; //copy of table


        foreach ($product_keys as $product_key => $product_value) { // $product_key is : time@ip+user_agent
            reset($cpy_product_keys);
            $time = self::separate_with($product_value, '@'); //the rounded time

            $counter = 0;

            foreach ($cpy_product_keys as $cpy_product_key => $cpy_product_value) {// value is empty at now
                if ($time == self::separate_with($cpy_product_value, '@'))
                    ++$counter;
            }

            $exist = $redis->hExists('temp', $time);

            if (!$exist)
                $redis->hIncrBy('temp', $time, $counter);// make temp hash-table and save  repeated Fields

        }

        $buffer = $redis->hGetAll('temp');
        $redis->delete('temp');

        return serialize($buffer);//serialize
    }


    /**
     * @param $type
     * @param $id
     * @param string $table_name
     * @return int
     */
    public static function get_moshaahede_statistic($type, $id, $table_name = 'hash_table')
    {
        $redis = self::connect_redis();

        if (self::empty_object($redis)) {
            return -1; //خطای اتصال به سرور
            //tood : connect to mysql
        }

        $key = self::make_product_key($type, $id);
        $product_visit_hash_table_name = $key;

        $product_visit_hash_table = $redis->hGetAll($product_visit_hash_table_name);
        $moshaahede = 0;

        foreach ($product_visit_hash_table as $user_agent_key => $user_agent_value) {
            $moshaahede += $moshaahede;
        }

        $old_visits = (int)$redis->hGet($table_name, $key);
        $today_visits = (int)$moshaahede;


        return $today_visits;
    }


    /**
     * @param $str
     * @param $char =  Character to separate str
     * @param int $getting_index
     * @return mixed
     */
    public static function separate_with($str, $char, $getting_index = 0)
    {
        return explode($char, $str)[$getting_index];
    }


    /**
     *
     * @param Redis $redis
     * @param $main_hash_table_keys
     * @param $current_index
     * @return bool
     */
    private static function is_last_valid_key(Redis $redis, $main_hash_table_keys, $current_index)
    {
        $_index = 0;
        foreach ($main_hash_table_keys as $product_key => $product_value) {

            if ($_index < $current_index) {
                ++$_index;
                continue;
            }//set cursor of loop

            $keys = $redis->hKeys($product_key);

            if (!empty($keys)) // some key is available yet
                return false;

        }
        return true;

    }


	
	

//    =============================                statistics functions               =============================


    /**
     *  *shh*
     * Return Hourly Visit Statistics For Chart.js
     * @param $target_time
     * @param $id
     * @param $type
     * @return array
     */
    public static function get_hourly_visit($target_time, $id, $type="product")
    {
        $seconds_in_each_day = 24 * 60 * 60;
//		$target_time += 0;
        $from = $target_time - $seconds_in_each_day ;
        $to = $target_time ;//+ $seconds_in_each_day;
		
//		return [$from,$to];
		
		$results =
            (new \yii\db\Query())
                ->select(['details'])
                ->from('eshop_visits')//eshop_visits
                ->where(['and', "created_at>=$from", "created_at<=$to"])
                ->andWhere(['and', "product_id =  $id"])
                ->andWhere(['and', "product_type = :type"], [':type' => $type])
                ->all();
//                ->createCommand()
//                ->sql;

        $results = self::make_hourly_visit_array($results, $target_time, $type . ':' . $id);

        return $results;
    }


    /**
     *  *shh*
     * Return The Array Of Hourly Visit | Make The Hourly Array For Chart.js
     * @param $results
     * @param $start_time
     * @param $hash_table_product_key
     * @return array
     */
    private static function make_hourly_visit_array($results, $start_time, $hash_table_product_key)
    {
        $daily_visits = array_fill(0, 25, 0);
		
//		$current_jalaali_date = new \DateTime("now", new \DateTimeZone('Asia/Tehran') );
//		$current_jalaali_hour =  (int)$current_jalaali_date->format('H');// A Hour
						
		// *shh*  get current date and target date to check, if the request want today chart, we use redis for hourly statistics.
        $_target_date = date("Y-m-d",$start_time);// gain target date 
        $_current_date = date("Y-m-d",time());// gain today date

//		echo $_current_date . ' - '. $_target_date;
        if ( $_current_date == $_target_date ) { // check for make today statistic
            // *shh* get today visits from redis
            $redis = self::connect_redis();
            $today_visits_from_redis = $redis->hGetAll($hash_table_product_key);

            $_hour = 1; //iterating hour
            for ($i = 0; $i <= 24; ++$i, ++$_hour) { // Hours In A Day

                foreach ($today_visits_from_redis as $key => $value) {
                    $_time = self::separate_with($key, '@'); //the rounded time(hour) in `type:id` hash table in $key
					
                    if ($_hour == $_time)
                        $daily_visits[$i]++;
                    
                }
            }

        } else { // read gotten data from mysql 
            foreach ($results as $result) {
                $result = unserialize($result['details']);// Index For Result keys
                $_hour = 1;// old solution => round($start_time / 3600)
//				echo $temp . '-';
                for ($i = 0; $i <= 24; ++$i, ++$_hour) {
                    if (isset($result["$_hour"]))
                       $daily_visits[$i] += $result["$_hour"];
                }
            }
        }

        return $daily_visits;
    }



    //    ================   Daily Statistics Functions    ================   
    /**
     *  *shh*
     * Return Daily Visit Statistics  For Chart.js
     * @param $target_month
     * @param $id
     * @param string $type
     * @return array
     */
    public static function get_daily_visit($target_month, $id, $type = "product")
    {

        $current_time = time(); // Milaadi Date And Time on Seconds
        $_time = $current_time; // Copy of current time
        $g_d = date('j', $current_time);
        $g_m = date('n', $current_time);
        $g_y = date('Y', $current_time);


        $current_time = FunctionsHelper::g2p($g_y, $g_m, $g_d); // Milaadi To Jaalali

        $current_month = $current_time[1];//select month
        $current_day = $current_time[2];//select day

        $different_in_months = $current_month - $target_month;

        $from = $_time - ( ($current_day-1) * 24 * 60 * 60) - ($different_in_months * 30 * 24 * 60 * 60); // convert the concepts on seconds

        if ($different_in_months == 0) {
            $to = $from + ($current_day-1) * 24 * 60 * 60; //  or + $_time
        } else {
            $to = $from + 30 * 24 * 60 * 60+1; // seconds of a month
            $current_day = 30; // cause we need to all statistics in in the last months
        }

        $results =
            (new \yii\db\Query())
                ->select(['visit', 'created_at'])
                ->from('eshop_visits')//eshop_visits
                ->where(['and', "created_at>=$from", "created_at<=$to"])
                ->andWhere(['and', "product_id =  $id"])
                ->andWhere(['and', "product_type = :type"], [':type' => $type])
                ->all();
//                ->createCommand()
//                ->sql;

        $results = self::get_daily_visit_array($results, $target_month, $current_day, $from,$type.':'.$id, $current_month);

        return $results;

    }

    /**
     *  *shh
     * Return The Array Of Daily Visit In 6 Last month| Make The Monthly Array For Chart.js
     * Pattern : ['month/day' : visit_count]
     * @param $query_result
     * @param $month
     * @param $day
     * @param $from | Start Time In Statistic
     * @return array
     */
    private static function get_daily_visit_array($query_result, $month, $current_day, $from , $observes_hash_table_key_name , $current_month)
    {
        $result_array = self::make_empty_array_daily_chart($month);

		foreach ($query_result as $query) { // maybe our cron runs 2 time each day, in this part i handle the multi record for one day of one product

            $_from = $from; //reset from time

            for ($i = 1; $i <= 30; ++$i)  // 1 month
            {
                $to = $_from + 86400; // 24*60*60=>86400 next day in seconds

                if ($query['created_at'] < $to && $query['created_at'] > $_from) {
                    $result_array["$month" . '/' . $i] += (int)$query['visit'];
                }

                $_from += 86400; // next day

            }
        }
		
		if ( $month==$current_month )
        {
             $redis = self::connect_redis();
             $result_array["$month" . '/' . $current_day] += (int) $redis->hLen($observes_hash_table_key_name);
        }
        return $result_array;
    }

    /**
     *   *shh*
     *  Make Empty Array With Date Indexing
     *  pattern: ['month/day' : 0]
     * @param $month
     * @return array
     */
    private static function make_empty_array_daily_chart($month)
    {
        $array = [];

        for ($i = 1; $i <= 30; ++$i)  // 1 month => 1*30 day
            $array["$month" . '/' . "$i"] = 0;

        return $array;
    }


}
