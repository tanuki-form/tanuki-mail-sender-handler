<?php

namespace Tanuki\PostHandler;

use Tanuki\AbstractHandler;
use Tanuki\Form;
use Tanuki\HandlerPipelineContext;
use Tanuki\HandlerResult;
use PHPMailer\PHPMailer\PHPMailer;

class MailSenderHandler extends AbstractHandler {
  public array $config = [];
  private PHPMailer $mailer;

  public function __construct(array $config = []) {
    $this->config = $config;
    $this->mailer = new PHPMailer(true);
    $this->mailer->Timeout = 5;

    // Charset
    $this->mailer->CharSet = $config['mailer']['charset'] ?? 'UTF-8';

    // SMTP
    if(isset($config['mailer']['smtp'])) {
      $smtp = $config['mailer']['smtp'];
      $this->mailer->isSMTP();
      $this->mailer->Host = $smtp['host'] ?? '';
      $this->mailer->SMTPAuth = $smtp['auth'] ?? true;
      $this->mailer->Username = $smtp['username'] ?? '';
      $this->mailer->Password = $smtp['password'] ?? '';
      $this->mailer->SMTPSecure = $smtp['secure'] ?? PHPMailer::ENCRYPTION_STARTTLS;
      $this->mailer->Port = $smtp['port'] ?? 587;
    } else {
      $this->mailer->isMail();
    }

    // From
    if(!empty($config['mailer']['from'])) {
      $this->mailer->setFrom(
        $config['mailer']['from'] ?? '',
        $config['mailer']['from_name'] ?? ''
      );
    }

    // Reply-To
    if(!empty($config['mailer']['reply_to'])) {
      $this->mailer->addReplyTo(
        $config['mailer']['reply_to'] ?? '',
        $config['mailer']['reply_to_name'] ?? ''
      );
    }

    // CC
    if(!empty($config['mailer']['cc'])) {
      foreach($config['mailer']['cc'] as $cc) {
        $this->mailer->addCC($cc);
      }
    }

    // BCC
    if(!empty($config['mailer']['bcc'])) {
      foreach($config['mailer']['bcc'] as $bcc) {
        $this->mailer->addBCC($bcc);
      }
    }

    // Subject
    if(!empty($config['mailer']['subject'])) {
      $this->mailer->Subject = $config['mailer']['subject'];
    }

    // To
    if(!empty($config['mailer']['to'])) {
      $this->mailer->addAddress($config['mailer']['to']);
    }
  }
  public function handle(Form $form, HandlerPipelineContext $context): HandlerResult {
    $data = $form->getNormalizedData();

    // Body
    if(!empty($this->config['body_template'])) {
      $twig = $this->getTwig();
      $template = $twig->createTemplate($this->config['body_template']);
      $this->mailer->Body = $template->render(['data' => $data]);
    }

    // To field
    if(!empty($this->config['mailer']['to_field']) && isset($data[$this->config['mailer']['to_field']])) {
      $this->mailer->addAddress($data[$this->config['mailer']['to_field']]);
    }

    // Send email
    $this->mailer->send();
    /*
    try {
      $this->mailer->send();
    } catch (\Exception $e) {
      // Handle error (log it, rethrow it, etc.)
    }
      */

    return $this->success();
  }

  private function getTwig(): \Twig\Environment {
    $loader = new \Twig\Loader\ArrayLoader([]);
    return new \Twig\Environment($loader);
  }
}
