<?php
    @include_once('assets/libraries/guzzlehttp/vendor/autoload.php');
    use Guzzle\Log\MessageFormatter;
    
    class Datasecurity
    {


        /**
         * Class datasecurity
         * Upload
         * Create folder
         * ....
         */

        /**
         * Create folder
         *
         * @param [type] $path
         * @return void
         */
        public function __construct()
        {
            $this->base_url = 'https://data.d2t.vn/remote.php/webdav/';
            $client = new GuzzleHttp\Client(['base_uri' => $this->base_url]);
          //  $response = $client->request('MKCOL', '/1232');
            echo "<pre>".print_r($client,1);die;
            $res= $client->request('PUT', 'upload/cmt11.jpg', [
                    'auth' => ['admin', '123456a@']
                ]);

            echo $res->getStatusCode();
            die('5555');
            //if (!file_exists('upload/')){
        }

        public function base_curl($url, $fileContent,$method)
        {
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 30,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_POSTFIELDS => file_get_contents($fileContent),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Basic ' . base64_encode("admin:123456a@")
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);
            $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            $data = ['status' => 0, 'message' => 'not success', 'http_code' => $http_code];
            if ($http_code == 201) {
                $data = ['status' => 1, 'message' => 'success', 'http_code' => $http_code];
            }
            if ($http_code == 405) {
                $data = ['status' => 1, 'message' => 'Folder exits', 'http_code' => $http_code];
            }
            echo json_encode($data);
            if ($err) {
                echo "cURL Error #:" . $err;
                //die('222');
            } else {
                echo $response;
               // die('333');
            }








        }


        function dataSecurity_uploadFile($dir, $filePath)
        {
            $url = $this->base_url.$dir."/.$filePath";
            $method = "PUT";
            $this->base_curl($url, $filePath,$method);
        }

        function makeFolder($folderPath)
        {
            $method = "MKCOL";
            $url = $this->base_url.$folderPath;
            $this->base_curl($url, $folderPath,$method);
        }
    }
    ?>