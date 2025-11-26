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
    $this->mailer->CharSet = $config['charset'] ?? 'UTF-8';

    // SMTP
    if(isset($config['smtp'])) {
      $smtp = $config['smtp'];
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
    if(!empty($config['from'])) {
      $this->mailer->setFrom($config['from'], $config['fromName'] ?? '');
    }

    // Reply-To
    if(!empty($config['replyTo'])) {
      $this->mailer->addReplyTo(
        $config['replyTo'] ?? '',
        $config['replyToName'] ?? ''
      );
    }

    // CC
    if(!empty($config['cc'])) {
      foreach($config['cc'] as $cc) {
        $this->mailer->addCC($cc);
      }
    }

    // BCC
    if(!empty($config['bcc'])) {
      foreach($config['bcc'] as $bcc) {
        $this->mailer->addBCC($bcc);
      }
    }

    // Subject
    if(!empty($config['subject'])) {
      $this->mailer->Subject = $config['subject'];
    }

    // To
    if(!empty($config['to'])) {
      $this->mailer->addAddress($config['to'], $config['toName'] ?? '');
    }
  }
  public function handle(Form $form, HandlerPipelineContext $context): HandlerResult {
    $data = $form->getNormalizedData();

    // Body
    if(!empty($this->config['bodyTemplate'])) {
      $twig = $this->getTwig();
      $template = $twig->createTemplate($this->config['bodyTemplate']);
      $this->mailer->Body = $template->render(['data' => $data]);
    }

    // To field
    if(!empty($this->config['toField']) && isset($data[$this->config['toField']])) {
      $this->mailer->addAddress($data[$this->config['toField']]);
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
