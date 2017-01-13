<?php


/**
 * Copyright (c) 2010-2017, AWHSPanel by Nicolas Méloni
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright notice,
 *       this list of conditions and the following disclaimer in the documentation
 *       and/or other materials provided with the distribution.
 *     * Neither the name of AWHSPanel nor the names of its contributors
 *       may be used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
 * OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
 * EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

namespace AWHS\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


class AccountController extends Controller
{
    public function creditAction()
    {
        // On récupère l'entité correspondant à l'id $id
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();

        return $this->render('AWHSUserBundle:' . $this->container->getParameter('awhs')['template'] . '/Account:credit.html.twig', array(
            'user' => $user,
        ));
    }

    public function productsAction()
    {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();

        $em = $this->getDoctrine()->getManager();

        $products = $em->getRepository('AWHSCoreBundle:SubscriptionProduct')->findAllProductsByUser($user);

        return $this->render('AWHSUserBundle:' . $this->container->getParameter('awhs')['template'] . '/Account:products.html.twig', array(
            'user' => $user,
            'products' => $products,
        ));
    }

    public function ipn_starpassAction()
    {
        // On récupère l'entité correspondant à l'id $id
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();


        $em = $this->getDoctrine()->getManager();

        /**
         * Some codes of this method are from https://www.starpass.fr/
         */
        $array = $this->xml2array(file_get_contents('http://script.starpass.fr/palier.php'));

        // Dé;claration des variables
        $ids = $idd = $codes = $code1 = $code2 = $code3 = $code4 = $code5 = $datas = '';
        $idp = 20370;
        // $ids n'est plus utilisé;, mais il faut conserver la variable pour une question de compatibilité;
        $idd = 317926;
        $ident = $idp . ";" . $ids . ";" . $idd;
        // On ré;cupère le(s) code(s) sous la forme 'xxxxxxxx;xxxxxxxx'
        if (isset($_POST['code1'])) $code1 = $_POST['code1'];
        if (isset($_POST['code2'])) $code2 = ";" . $_POST['code2'];
        if (isset($_POST['code3'])) $code3 = ";" . $_POST['code3'];
        if (isset($_POST['code4'])) $code4 = ";" . $_POST['code4'];
        if (isset($_POST['code5'])) $code5 = ";" . $_POST['code5'];
        $codes = $code1 . $code2 . $code3 . $code4 . $code5;
        // On ré;cupère le champ DATAS
        if (isset($_POST['DATAS'])) $datas = $_POST['DATAS'];
        // On encode les trois chaines en URL
        $ident = urlencode($ident);
        $codes = urlencode($codes);
        $datas = urlencode($datas);

        /* Envoi de la requête vers le serveur StarPass
        Dans la variable tab[0] on ré;cupère la ré;ponse du serveur
        Dans la variable tab[1] on ré;cupère l'URL d'accès ou d'erreur suivant la ré;ponse du serveur */
        $get_f = @file("http://script.starpass.fr/check_php.php?ident=$ident&codes=$codes&DATAS=$datas");
        if (!$get_f) {
            exit("Votre serveur n'a pas accès au serveur de Starpass, merci de contacter votre hébergeur.");
        }
        $tab = explode("|", $get_f[0]);

        if (!$tab[1]) $url = "OrderCancel.php";
        else $url = $tab[1];

        // dans $pays on a le pays de l'offre. exemple "fr"
        $pays = $tab[2];
        // dans $palier on a le palier de l'offre. exemple "Plus A"
        $palier = urldecode($tab[3]);
        // dans $id_palier on a l'identifiant de l'offre
        $id_palier = urldecode($tab[4]);
        // dans $type on a le type de l'offre. exemple "sms", "audiotel, "cb", etc.
        $type = urldecode($tab[5]);
        // vous pouvez &agrave; tout moment consulter la liste des paliers &agrave; l'adresse : http://script.starpass.fr/palier.php

        // Si $tab[0] ne ré;pond pas "OUI" l'accès est refusé;
        // On redirige sur l'URL d'erreur

        if (substr($tab[0], 0, 3) != "OUI") {
            //Rediriger sur la page d'erreur
            exit("ERROR");
        } else {
            /* Le serveur a répondu "OUI"*/
            foreach ($array as $key => $value) {
                foreach ($value as $key1 => $value1) {
                    foreach ($value1 as $value2) {
                        if ($value2['id_palier'] == $id_palier) {
                            $info_id_palier = $value2['id_palier'];
                            $info_name = $value2['name'];
                            $info_type = $value2['type'];
                            $info_country = $value2['country'];
                            $info_numtel = $value2['numtel'];
                            $info_price = $value2['price'];
                            $info_currency = $value2['currency'];
                        }
                    }
                }
            }
            if ($info_type == "cb" || $info_type == "wha" || $info_type == "paypal" || $info_type == "neosurf") {
                $user->setMoney((float)$user->getMoney() + (float)$info_price);
                $em->persist($user);
                $em->flush();
            } else {
                $user->setMoney((float)$user->getMoney() + (float)$info_price);
                $em->persist($user);
                $em->flush();
            }
        }

        return $this->render('AWHSUserBundle:' . $this->container->getParameter('awhs')['template'] . '/Account:starpass_success.html.twig', array(
            'user' => $user,
        ));
    }

    /**
     * xml2array() will convert the given XML text to an array in the XML structure.
     * Link: http://www.bin-co.com/php/scripts/xml2array/
     * Arguments : $contents - The XML text
     *                $get_attributes - 1 or 0. If this is 1 the function will get the attributes as well as the tag values - this results in a different array structure in the return value.
     *                $priority - Can be 'tag' or 'attribute'. This will change the way the resulting array sturcture. For 'tag', the tags are given more importance.
     * Return: The parsed XML in an array form. Use print_r() to see the resulting array structure.
     * Examples: $array =  xml2array(file_get_contents('feed.xml'));
     *              $array =  xml2array(file_get_contents('feed.xml', 1, 'attribute'));
     * @param $contents
     * @param int $get_attributes
     * @param string $priority
     * @return array
     */
    function xml2array($contents, $get_attributes = 1, $priority = 'tag')
    {
        if (!$contents) return array();

        if (!function_exists('xml_parser_create')) {
            //print "'xml_parser_create()' function not found!"; 
            return array();
        }

        //Get the XML parser of PHP - PHP must have this module for the parser to work 
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss 
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($contents), $xml_values);
        xml_parser_free($parser);

        if (!$xml_values) return;//Hmm... 

        //Initializations 
        $xml_array = array();
        $parents = array();
        $opened_tags = array();
        $arr = array();

        $current = &$xml_array; //Refference 

        //Go through the tags. 
        $repeated_tag_index = array();//Multiple tags with same name will be turned into an array 
        foreach ($xml_values as $data) {
            unset($attributes, $value);//Remove existing values, or there will be trouble 

            //This command will extract these variables into the foreach scope 
            // tag(string), type(string), level(int), attributes(array). 
            extract($data);//We could use the array by itself, but this cooler. 

            $result = array();
            $attributes_data = array();

            if (isset($value)) {
                if ($priority == 'tag') $result = $value;
                else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
            }

            //Set the attributes too. 
            if (isset($attributes) and $get_attributes) {
                foreach ($attributes as $attr => $val) {
                    if ($priority == 'tag') $attributes_data[$attr] = $val;
                    else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr' 
                }
            }

            //See tag status and do the needed. 
            if ($type == "open") {//The starting of the tag '<tag>' 
                $parent[$level - 1] = &$current;
                if (!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                    $current[$tag] = $result;
                    if ($attributes_data) $current[$tag . '_attr'] = $attributes_data;
                    $repeated_tag_index[$tag . '_' . $level] = 1;

                    $current = &$current[$tag];

                } else { //There was another element with the same tag name 

                    if (isset($current[$tag][0])) {//If there is a 0th element it is already an array 
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        $repeated_tag_index[$tag . '_' . $level]++;
                    } else {//This section will make the value an array if multiple tags with the same name appear together
                        $current[$tag] = array($current[$tag], $result);//This will combine the existing item and the new item together to make an array
                        $repeated_tag_index[$tag . '_' . $level] = 2;

                        if (isset($current[$tag . '_attr'])) { //The attribute of the last(0th) tag must be moved as well
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset($current[$tag . '_attr']);
                        }

                    }
                    $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                    $current = &$current[$tag][$last_item_index];
                }

            } elseif ($type == "complete") { //Tags that ends in 1 line '<tag />' 
                //See if the key is already taken. 
                if (!isset($current[$tag])) { //New Key 
                    $current[$tag] = $result;
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' and $attributes_data) $current[$tag . '_attr'] = $attributes_data;

                } else { //If taken, put all things inside a list(array) 
                    if (isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array... 

                        // ...push the new element into that array. 
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;

                        if ($priority == 'tag' and $get_attributes and $attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag . '_' . $level]++;

                    } else { //If it is not an array... 
                        $current[$tag] = array($current[$tag], $result); //...Make it an array using using the existing value and the new value
                        $repeated_tag_index[$tag . '_' . $level] = 1;
                        if ($priority == 'tag' and $get_attributes) {
                            if (isset($current[$tag . '_attr'])) { //The attribute of the last(0th) tag must be moved as well

                                $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                                unset($current[$tag . '_attr']);
                            }

                            if ($attributes_data) {
                                $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                            }
                        }
                        $repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken 
                    }
                }

            } elseif ($type == 'close') { //End of tag '</tag>' 
                $current = &$parent[$level - 1];
            }
        }

        return ($xml_array);
    }

    public function ipn_starpass_successAction()
    {
        // On récupère l'entité correspondant à l'id $id
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();


        return $this->render('AWHSUserBundle:' . $this->container->getParameter('awhs')['template'] . '/Account:starpass_success.html.twig', array(
            'user' => $user,
        ));
    }
}
