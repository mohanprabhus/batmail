<?php

/**
 * MIT License
 *
 * Copyright (C) 2016 - Sebastien Malot <sebastien@malot.fr>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Smalot\Batmail\Commands;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Class SendCommand
 * @package Smalot\Batmail\Commands
 */
class SendCommand extends Command
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SendCommand constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        parent::__construct();

        $this->logger = $logger;
    }

    /**
     * @param string $message
     * @param int $mode
     */
    public function debug($message, $mode)
    {
        if (!is_null($this->logger)) {
            $this->logger->debug($message, ['mode' => $mode]);
        }
    }

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
          ->setName('send')
          ->addArgument('file', InputArgument::OPTIONAL, 'File', 'index.html')
          ->addOption('to', null, InputOption::VALUE_REQUIRED, 'To')
          ->setDescription('Send mail.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $filename = $input->getArgument('file');

        if (!file_exists($filename)) {
            throw new \InvalidArgumentException('File not found');
        }

        if (!($to = $input->getOption('to'))) {
            throw new \InvalidArgumentException('Missing destination');
        }

        $phpMailer = $this->getPHPMailer();
        $phpMailer->addAddress($to);

        $phpMailer->Body = $this->prepareBody($filename, $phpMailer);

        if (!$phpMailer->send()) {
            if (!is_null($this->logger)) {
                $this->logger->warning('An issue occurs while sending mail using SMTP.');
            }

            return 2;
        }

        if (!is_null($this->logger)) {
            $this->logger->notice('Mail successfully sent using SMTP.');
        }
    }

    /**
     * @return \PHPMailer
     */
    protected function getPHPMailer()
    {
        $phpMailer = new \PHPMailer();
        $phpMailer->isMail();
        $phpMailer->IsHTML(true);
        $phpMailer->CharSet = 'utf-8';
        $phpMailer->Subject = 'Mail sent by Batmail :-)';

        return $phpMailer;
    }

    /**
     * @param string $filename
     * @param \PHPMailer $phpMailer
     * @return string
     */
    protected function prepareBody($filename, $phpMailer)
    {
        $content = file_get_contents($filename);
        $stack = [];

        $content = preg_replace_callback(
          '/src="(.*?)"/mis',
          function ($match) use ($phpMailer, &$stack) {
              if (!preg_match('/^https?:\/\//', $match[1])) {
                  if (isset($stack[$match[1]])) {
                      $cid = $stack[$match[1]];
                  } else {
                      $cid = 'image_'.count($stack);
                      $phpMailer->addEmbeddedImage($match[1], $cid, basename($match[1]));
                      $stack[$match[1]] = $cid;
                  }

                  return 'src="cid:'.$cid.'"';
              } else {
                  return $match[0];
              }
          },
          $content
        );

        return $content;
    }
}
