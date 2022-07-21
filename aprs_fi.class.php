<?php
/*
    aprs.fi API PHP Class
    Copyright (C) 2022  Bohdan Manko UR5WKM

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

class aprs_fi
{
    private string $base = "https://api.aprs.fi/api/";

    private string $user_agent = "";

    private string $api_key = "";

    private $http_code = NULL;

    public function __construct($api_key, $user_agent = NULL, $base = NULL)
    {
        $this->api_key = $api_key;
        $this->user_agent = $user_agent ?? "PHP/" . phpversion();
        if ($base) $this->base = $base;
    }

    public function get_url($endpoint, $params = array()): string
    {
        return $this->base . $endpoint . (count($params) > 0 ? "?" . http_build_query($params) : "");
    }

    public function get($params = array(), $headers = array())
    {
        $params = array_merge($params, array(
            "apikey" => $this->api_key,
            "format" => "json"
        ));
        return $this->get_json($this->get_url("get", $params));
    }

    public function get_http_code()
    {
        return $this->http_code;
    }

    private function get_json($url, $post = NULL, $headers = array(), $method = NULL)
    {
        $content = $this->get_content($url, $post, $headers, $method);

        return json_decode($content);
    }

    private function get_content($url, $post = NULL, $headers = array(), $method = NULL)
    {
        $handler = curl_init();
        curl_setopt($handler, CURLOPT_URL, $url);
        curl_setopt($handler, CURLOPT_HEADER, FALSE);
        curl_setopt($handler, CURLOPT_HTTPHEADER, $headers);
        if ($post || $method !== NULL) {
            if ($method !== NULL) {
                curl_setopt($handler, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($handler, CURLOPT_POST, FALSE);
            } else {
                curl_setopt($handler, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($handler, CURLOPT_POST, TRUE);
            }
            curl_setopt($handler, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($handler, CURLINFO_HEADER_OUT, FALSE);
        curl_setopt($handler, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($handler, CURLOPT_MAXREDIRS, 10);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($handler, CURLOPT_TIMEOUT, 30);
        curl_setopt($handler, CURLOPT_USERAGENT, $this->user_agent);
        $result = curl_exec($handler);
        $this->http_code = curl_getinfo($handler, CURLINFO_HTTP_CODE);
        curl_close($handler);


        return $result;
    }
}