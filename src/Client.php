<?php

namespace Lbryum;

/**
 * User: Mathew Waller <wallermadev@lbry.io>
 */
class Client
{

    /**
     * @var string JSONRPC Host
     */
    protected $host;

    /**
     * @var integer JSONRPC Port
     */
    protected $port;

    /**
     * @var integer Last Message-ID
     */
    protected $id;

    /**
     * Last occurred error
     *
     * @var null
     */
    protected $lastError = null;

    /**
     * Lbryum constructor.
     * @param string $host
     * @param int $port
     * @param int $id
     */
    public function __construct($host = 'http://127.0.0.1', $port = 7777, $id = 0)
    {
        $this->setHost($host);
        $this->setPort($port);
        $this->setId($id);
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     *
     * @return Lbryum
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param int $port
     *
     * @return Lbryum
     */
    public function setPort($port)
    {
        $this->port = $port;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns current ID + 1
     *
     * @return int
     */
    public function getNextId()
    {
        return ++$this->id;
    }

    /**
     * @param int $id
     *
     * @return Lbryum
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return null
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    /**
     * @param null $lastError
     *
     * @return Lbryum
     */
    private function setLastError($lastError)
    {
        $this->lastError = $lastError;

        return $this;
    }

    /**
     * @param       $method
     * @param array $params
     *
     * @return bool|array
     */
    public function SendRequest($method, array $params = [])
    {
        // ### Build request string
        $request = json_encode([
            'method' => $method,
            'params' => $params,
            'id'     => $this->getNextId(),
        ]);

        // ### Replace braces
        $request = str_replace(['[{', '}]'], ['{', '}'], $request);

        // ### Create CURL instance
        $curl = curl_init(vsprintf(
            '%s:%s', [$this->getHost(), $this->getPort()]
        ));

        // ### Set some options we need
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $request,
        ]);

        // ### Execute request & convert data to array
        $response = curl_exec($curl);

        // ### Catch error if occured
        $error = curl_error($curl);

        // ### Check if request was successfull
        if ($error) {

            // ### Set last error, so user can catch it
            $this->setLastError($error);

            // ### Return a false, so the user knows something went wrong
            return false;

        }

        // ### Return Data converted to an array
        $response = json_decode($response, true);

        // ### Check if an error occured
        if(isset($response['error'])) {

            // ### Set message
            $this->setLastError($response['error']['message']);

            // ###
            return false;
        }

        return $response['result'];
    }

    /**
     * Return the version of lbryum.
     *
     * @return array|bool
     */
    public function GetVersion()
    {
        return $this->SendRequest('version');
    }

    /**
     * Return wallet synchronization status
     * @Todo: This thing is broken, not my fault.
     *
     * @return array|bool
     */
    public function isSynchronized()
    {
        return $this->SendRequest('is_synchronized');
    }

    /**
     * Get current balance
     *
     * @return bool|array
     */
    public function GetBalance()
    {
        return $this->SendRequest('getbalance');
    }

    /**
     * Return the balance of any address. Note: This is a walletless server query, results are not checked by SPV.
     *
     * @param $address      string  LBC address
     * @return array|bool
     */
    public function GetAddressBalance($address)
    {
        return $this->SendRequest('getaddressbalance', ['address' => $address]);
    }

    /**
     * Return the transaction history of any address. Note: This is a walletless server query, results are not checked by SPV.
     *
     * @param $address      string  LBC Address
     * @return array|bool
     */
    public function GetAddressHistory($address)
    {
        return $this->SendRequest('getaddresshistory', ['address' => $address]);
    }

    /**
     * Returns the UTXO list of any address. Note: This is a walletless server query, results are not checked by SPV.
     *
     * @param $address      string  LBC Address
     * @return array|bool
     */
    public function GetAddressUnspent($address)
    {
        return $this->SendRequest('getaddressunspent', ['address' => $address]);
    }

    /**
     * Check if address is in wallet. Return true if and only address is in wallet
     * @Todo: This thing is broken, not my fault.
     *
     * @param $address
     *
     * @return array|bool
     */
    public function isAddressMine($address)
    {
        return $this->SendRequest('ismine', ['address' => $address]);
    }

    /**
     * Wallet history. Returns the transaction history of your wallet.
     *
     * @return bool|array
     */
    public function GetHistory()
    {
        return $this->SendRequest('history');
    }

    /**
     * Create a payment request.
     *
     * @param      $amount          integer     LBC amount to request
     * @param null $memo            string      Description of the request
     * @param null $expiration      integer     Time in seconds
     *
     * @return bool|array
     */
    public function AddRequest($amount, $memo = null, $expiration = null /*, $force = false */)
    {
        $params = ['amount' => $amount];

        if($memo !== null) {
            $params['memo'] = $memo;
        }

        if($expiration !== null) {
            $params['expiration'] = $expiration;
        }

        return $this->SendRequest('addrequest', $params);
    }

    /**
     * List the payment requests you made.
     *
     * @return bool|array
     */
    public function GetRequests()
    {
        return $this->SendRequest('listrequests');
    }

    /**
     * Return a payment request
     * @param $address          string  LBC Address
     *
     * @return bool|array
     */
    public function GetRequest($address)
    {
        return $this->SendRequest('getrequest', ['key' => $address]);
    }

    /**
     * Remove a payment request
     *
     * @param $address      string  LBC Address
     * @return array|bool
     */
    public function RemoveRequest($address)
    {
        return $this->SendRequest('rmrequest', ['address' => $address]);
    }

    /**
     * Remove all payment requests
     *
     * @return bool|array
     */
    public function ClearRequests()
    {
        return $this->SendRequest('clearrequests');
    }

    /**
     * Sign payment request with an OpenAlias
     *
     * @param $address      string  LBC Address
     * @return array|bool
     */
    public function SignRequest($address)
    {
        return $this->SendRequest('signrequest', ['address' => $address]);
    }

    /**
     * Broadcast a transaction to the network.
     *
     * @param $tx   string  Serialized transaction (hexadecimal)
     * @return array|bool
     */
    public function Broadcast($tx)
    {
        return $this->SendRequest('broadcast', ['tx' => $tx]);
    }

    /**
     * Create a transaction from json inputs. Inputs must have a redeemPubkey.
     * Outputs must be a list of (address, value).
     *
     * @param $jsontx
     * @return array|bool
     */
    public function Serialize($jsontx)
    {
        return $this->SendRequest('serialize', ['jsontx' => $jsontx]);
    }

    /**
     * Deserialize a serialized transaction
     *
     * @param $tx string Serialized transaction (hexadecimal)
     * @return array|bool
     */
    public function Deserialize($tx)
    {
        return $this->SendRequest('deserialize', ['tx' => $tx]);
    }

    /**
     * Encrypt a message with a public key. Use quotes if the message contains whitespaces.
     *
     * @param $pubkey   string  Public key
     * @param $message  string  Clear text message. Use quotes if it contains spaces.
     *
     * @return array|bool
     */
    public function Encrypt($pubkey, $message)
    {
        return $this->SendRequest('encrypt', [
            'pubkey'    => $pubkey,
            'message'   => $message,
        ]);
    }

    /**
     * Decrypt a message encrypted with a public key.
     *
     * @param $pubkey       string  Public key
     * @param $encrypted    string  Encrypted message
     * @return array|bool
     */
    public function Decrypt($pubkey, $encrypted)
    {
        return $this->SendRequest('decrypt', [
            'pubkey'    => $pubkey,
            'encrypted' => $encrypted,
        ]);
    }

    /**
     * Check that a seed was generated with given entropy.
     *
     * @param $seed string  Seed phrase
     * @return array|bool
     */
    public function CheckSeed($seed)
    {
        return $this->SendRequest('check_seed', ['seed' => $seed]);
    }

    /**
     * Create a seed
     *
     * @return array|bool
     */
    public function CreateSeed()
    {
        return $this->SendRequest('make_seed');
    }

    /**
     * Get seed phrase. Print the generation seed of your wallet.
     *
     * @return array|bool
     */
    public function GetSeed()
    {
        return $this->SendRequest('getseed');
    }

    /**
     * Freeze address. Freeze the funds at one of your wallet's addresses
     *
     * @param $address      string  LBC address
     * @return array|bool
     */
    public function Freeze($address)
    {
        return $this->SendRequest('freeze', ['address' => $address]);
    }

    /**
     * Return a configuration variable.
     *
     * @param $key      string  Config variable
     *
     * @return array|bool
     */
    public function GetConfig($key)
    {
        return $this->SendRequest('getconfig', ['key' => $key]);
    }

    /**
     * Set a configuration variable. 'value' may be a string or a Python expression.
     *
     * @param $key      string  Config variable
     * @param $value    string  Value
     *
     * @return array|bool
     */
    public function SetConfig($key, $value)
    {
        return $this->SendRequest('setconfig', [
            'key'   => $key,
            'value' => $value,
        ]);
    }

    /**
     * Get all claims in a given channel uri.
     *
     * @param $uri      string  uri for the channel
     *
     * @return array|bool
     */
    public function GetClaimsInChannel($uri){
        return $this->SendRequest('getclaimsinchannel', ['uri' => $uri]);
    }


    /**
     * Get all claims with the name $name in a channel $uri.
     *
     * @param $uri      string  uri for the channel
     * @param $name      string  name to find
     *
     * @return array|bool
     */
    public function GetClaimsInChannelWithName($uri, $name){
        return $this->SendRequest('getclaimsinchannelwithname', ['uri' => $uri, 'name' => $name]);
    }


    /**
     * Get claim with $claim_id
     *
     * @param $claim_id      string  The id of the claim.
     *
     * @return array|bool
     */
    public function GetClaimById($claim_id){
        return $this->SendRequest('getclaimbyid', ['claim_id' => $claim_id]);
    }

    /**
     * Get claim with $txid
     *
     * @param $txid      string  the transaction id to check for claims
     *
     * @return array|bool
     */
    public function GetClaimsInTx($txid){
        return $this->SendRequest('getclaimsfromtx', ['txid' => $txid]);
    }


    /**
     * Get claim with $txid
     *
     * @param $txid      string  the transaction id to check for claims
     *
     * @return array|bool
     */
    public function GetClaimsByOutPoint($txid){
        return $this->SendRequest('getclaimbyoutpoint', ['txid' => $txid]);
    }

    /**
     * Get nth($n) claim with name $name
     *
     * @param $name      string  name to find
     * @param $n      string  claim to return
     *
     * @return array|bool
     */
    public function GetNthClaimsByName($name, $n){
        return $this->SendRequest('getnthclaimforname', ['name' => $name, 'n' => $n]);
    }

    /**
     * Get claims signed by $claim_id
     *
     * @param $claim_id      string  the claim identifyer
     *
     * @return array|bool
     */
    public function GetClaimsSignedBy($name, $n){
        return $this->SendRequest('getclaimssignedby', ['claim_id' => $claim_id]);
    }

    /**
     * Check if a URI Resolves
     *
     * @param $uri      string  Uri of claim
     *
     * @return array|bool
     */
    public function GetClaimValueByUri($uri){
        return $this->SendRequest('getvalueforuri', ['uri' => $uri]);
    }

    /**
     *  Request value of name from lbryum server and verify its proof
     *
     * @param $uri      string  Uri of claim
     *
     * @return array|bool
     */
    public function GetClaimValueByName($name){
        return $this->SendRequest('getvalueforuri', ['uri' => $uri]);
    }


}