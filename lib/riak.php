<?php
/*********************************************************
 * default RIAK handler with CURL
 *********************************************************/

class riak {
    
    public $host;       // riak ip
    public $port;       // riak port
    private $timeout=5; // default timeout for connecting to riak
    
    public function __construct($host, $port) {
        $this->host=$host;
        $this->port=$port;
        $this->timeout=$timeout;
        if (($this->host) && ($this->port)){
            // check if the node is alive, if not return false
            $ch = curl_init();
            $url = 'http://' . $this->host . '/ping';

            curl_setopt( $ch, CURLOPT_URL, $url );
            curl_setopt( $ch, CURLOPT_PORT, $this->port );
            curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
            curl_setopt( $ch, CURLOPT_ENCODING, "" );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
            curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
            curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $this->timeout );
            curl_setopt( $ch, CURLOPT_TIMEOUT, $this->timeout );
            curl_setopt( $ch, CURLOPT_MAXREDIRS, 0 );

            $content = curl_exec( $ch );
            curl_close ( $ch );
            if ($content == "OK"){ return true; }
       }
       return false;
    }
    
    public function getKey ($bucket, $key) {
        // returns the $key value from RIAK
        $ch = curl_init();
        $url = 'http://' . $this->host . ':' . $this->port . '/riak/' . $bucket . '/' . $key;

        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_PORT, $this->port );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
        curl_setopt( $ch, CURLOPT_ENCODING, "" );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_AUTOREFERER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $this->timeout );
        curl_setopt( $ch, CURLOPT_TIMEOUT, $this->timeout );
        curl_setopt( $ch, CURLOPT_MAXREDIRS, 0 );

        $content = curl_exec( $ch );
        curl_close ( $ch );
        return $content;
    }
    
    public function setKey ($bucket, $key_name, $fields) {
        // writes to RIAK the key=>value
        // this function writes only json, use the setKeyB to put binary data
        // returns true if the write was successful
        $ch = curl_init();
        $url = 'http://' . $this->host . '/riak/' . $bucket . '/' . $key_name . '?returnbody=false';

        curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_PORT, $this->port );
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false );
        curl_setopt($ch, CURLOPT_ENCODING, "" );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout );
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout );
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0 );
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        
        $content = curl_exec( $ch );
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ( $ch );
        if (($status == "201") || ($status == "204") || ($stats == "300")){
            return true;
        }
        else {
            return false;
        }
    }
    
    public function setKeyB ($bucket, $key, $file_name) {
        // writes to RIAK the key=>value bynary
        // this function is limited to 50 mbytes files
        // this function writes only binary, use the setKey to put json
        // returns true if the write was successful
        $ch = curl_init();
        $url = 'http://' . $this->host . '/riak/' . $bucket . '/' . $key . '?returnbody=false';

        curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_PORT, $this->port );
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false );
        curl_setopt($ch, CURLOPT_ENCODING, "" );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout );
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout );
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0 );
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, TRUE);      # --data-binary !!!
        curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: image/jpeg'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($file_name));
        
        if (filesize($file_name) > 50000000) {
            return false;
        }
        $content = curl_exec( $ch );
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ( $ch );
        if (($status == "201") || ($status == "204") || ($stats == "300")){
            return true;
        }
        else {
            return false;
        }
    }
    
    public function findKeys ($bucket, $search, $opt) {
        /* search in a specified bucket a key:value, returns array of results
         * first make sure that the bucket is searchable by activating the search on it
         *
         * curl -XPUT -H "content-type:application/json" http://192.168.75.128:8098/riak/users -d '{"props":{"precommit":[{"mod":"riak_search_kv_hook","fun":"precommit"}]}}'
         * 
         */
        
        $ch = curl_init();
        $url = 'http://' . $this->host . ':' . $this->port . '/solr/' . $bucket . '/select?wt=json&q=' . $search . '&q.opt=' . $opt;

        curl_setopt( $ch, CURLOPT_URL, $url );
        curl_setopt( $ch, CURLOPT_PORT, $this->port );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, false );
        curl_setopt( $ch, CURLOPT_ENCODING, "" );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt( $ch, CURLOPT_AUTOREFERER, false );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $this->timeout );
        curl_setopt( $ch, CURLOPT_TIMEOUT, $this->timeout );
        curl_setopt( $ch, CURLOPT_MAXREDIRS, 0 );

        $content = curl_exec( $ch );
        curl_close ( $ch );

        $x=json_decode($content, true);
        return $x['response']['docs'];
    }
    
    public function delKey ($bucket, $key){
        // deletes one key from one bucket
        // returns true if successful or false otherwise
        $ch = curl_init();
        $url = 'http://' . $this->host . '/riak/' . $bucket . '/' . $key;

        curl_setopt($ch, CURLOPT_URL, $url );
        curl_setopt($ch, CURLOPT_PORT, $this->port );
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false );
        curl_setopt($ch, CURLOPT_ENCODING, "" );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true );
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false );    # required for https urls
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout );
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout );
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0 );
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        
        $res = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close ( $ch );
        if (($status == "204") || ($status == "404")){
            return true;
        }
        else {
            return false;
        }
    }
    
    public function isAlive () {
        // returns true if the node is alive, or false if not
    }
}