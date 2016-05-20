<?php

namespace Parp\MainBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use SensioLabs\AnsiConverter\AnsiToHtmlConverter;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

class PublishController extends Controller
{

    /**
     * @Route("/publish/{showonly}", name="publish", defaults={"showonly" : 1})
     */
    public function publishAction($showonly)
    {
        $kernel = $this->get('kernel');
        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArrayInput(array(
           'command' => 'parp:ldapsave',
           'showonly' => $showonly
           //'--message-limit' => $messages,
        ));
        
        // You can use NullOutput() if you don't need the output
        $output = new BufferedOutput(
            OutputInterface::VERBOSITY_NORMAL,
            true // true for decorated
        );
        $application->run($input, $output);

        // return the output, don't use if you used NullOutput()
        $content = $output->fetch();
        
        $converter = new AnsiToHtmlConverter();
        
        // return new Response(""), if you used NullOutput()
        return $this->render('ParpMainBundle:Publish:publish.html.twig', array('showonly' => $showonly, 'content' => $converter->convert($content)));
    }
    
}