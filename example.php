<?php

require 'aws/aws-autoloader.php';

use \Aws\Ec2\Ec2Client;


// This is the instance we will work in this example
$instance_id = 'i-12345678912345678';


/****************************************************************************************************
Class to handle an EC2 instance
****************************************************************************************************/

class InstanceHandle {

    // Object from the connect EC2 call
    private $ec2_client;

    // Information to authenticate with AWS
    private $AWS_AUTH_EC2_KEY = '';
    private $AWS_AUTH_EC2_SECRET = '';
    private $AWS_AUTH_EC2_REGION = '';
    private $AWS_AUTH_EC2_VERSION = '2015-10-01';

    // Instance ID to be handled
    public $instance_id;


    public function __construct($instance_id)
    {
        /**
        Constructor of this class.
        Here we connect to the AWS using the authentication information.

        :param $instance_id String:
            The ID of the Instance. Can be found in the EC2 page.
        */

        $this->instance_id = $instance_id;

        // Connect to our AWS account
        $this->ec2_client = Ec2Client::factory(
            array(
                'credentials' => array(
                    'key' => $this->AWS_AUTH_EC2_KEY,
                    'secret' => $this->AWS_AUTH_EC2_SECRET
                ),
                'region' => $this->AWS_AUTH_EC2_REGION,
                'version' => $this->AWS_AUTH_EC2_VERSION
            )
        );
    }


    public function GetInstanceInformation()
    {
        /**
        Get information about the instance

        :return:
            Array : An array contains the instance information
            FALSE : It was not possible to reach the instance
        */

        // Get instance using API
        $result = $this->ec2_client->describeInstances(array('InstanceIds' => array($this->instance_id)));
        $instance = $result->get('Reservations')[0]['Instances'][0];

        // Get information from the query above
        if ($this->instance_id == $instance['InstanceId']) {
            $instance_name = $instance['Tags'][0]['Value'];
            $instance_state = $instance['State']['Name'];
            $instance_public_dns = $instance['PublicDnsName'];
            $instance_public_ip = (array_key_exists('PublicIpAddress', $instance) ? $instance['PublicIpAddress'] : 'unkown');

            return array(
                'Name' => $instance_name,
                'State' => $instance_state,
                'Public_DNS' => $instance_public_dns,
                'Public_IP' => ($instance_public_ip ? $instance_public_ip : 'unkown')
            );
        }
        else {
            return false;
        }
    }

    public function GetInstanceStatus()
    {
        /**
        Get just the status of the instance

        :return:
            String : The status
            FALSE : It was not posible to get the status
        */

        if ($state = $this->GetInstanceInformation()) {
            return $state['State'];
        }
        else {
            return false;
        }
    }


    public function ActivateInstance()
    {
        /**
        Activate the instance
        */

        $result = $this->ec2_client->startInstances(
            array(
                'InstanceIds' => array($this->instance_id),
                'DryRun' => false
            )
        );
    }

    public function DeactivateInstance()
    {
        /*
        Deactivate the instace
        */

        $result = $this->ec2_client->stopInstances(
            array(
                'InstanceIds' => array($this->instance_id),
                'DryRun' => false
            )
        );
    }

}


/****************************************************************************************************
Example of the InstanceHandle usage.
Here I just demonstrate the functions in the class InstanceHandle.
****************************************************************************************************/

// Define the output
header("Content-Type: text/html");

// Create our object passing the instance id we want to handle
$instance_handle = new InstanceHandle($instance_id);

// Output de instance information
if ($instance = $instance_handle->GetInstanceInformation()) {
    $instance_name = $instance['Name'];
    $instance_state = $instance['State'];
    $instance_public_dns = $instance['Public_DNS'];
    $instance_public_ip = $instance['Public_IP'];
}

echo 'Instance information:<br>';
echo '<b>Name:</b> '.$instance_name.'<br>';
echo '<b>Public DNS:</b> '.$instance_public_dns.'<br>';
echo '<b>Public IP:</b> '.$instance_public_ip.'<br>';
echo '<b>State:</b> '.$instance_state.'<br>';
echo '<br>';


// Activate the instance
$instance_handle->ActivateInstance();
echo 'Activating the instance<br>';
ob_flush();
flush();


// Necessary here because we have a short delay between operation/status into AWS
sleep(2);


// Get the instance styatus
if ($status = $instance_handle->GetInstanceStatus()) {
    echo '<b>Status just after the command:</b> '.$status.'<br>';
    echo '<br>';
    ob_flush();
    flush();
}


// Deactivate the instance
$instance_handle->DeactivateInstance();
echo 'Deactivating the instance<br>';
ob_flush();
flush();


// Necessary here because we have a short delay between operation/status into AWS
sleep(2);


// Get the instance status
if ($status = $instance_handle->GetInstanceStatus()) {
    echo '<b>Status just after the command:</b> '.$status.'<br>';
    echo '<br>';
    ob_flush();
    flush();
}


echo 'This example is done<br>';
echo '<br>';
ob_flush();
flush();
