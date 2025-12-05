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
  }

  public function handle(Form $form, HandlerPipelineContext $context): HandlerResult {
    $data = $form->getNormalizedData();

    try {
      $this->mailer = new PHPMailer(true);
      $this->mailer->Timeout = $this->config["timeout"] ?? 60;

      // Charset
      $this->mailer->CharSet = $this->config["charset"] ?? "UTF-8";

      // SMTP
      if(isset($this->config["smtp"])) {
        $smtp = $this->config["smtp"];
        $this->mailer->isSMTP();
        $this->mailer->Host = $smtp["host"] ?? "";
        $this->mailer->SMTPAuth = $smtp["auth"] ?? true;
        $this->mailer->Username = $smtp["username"] ?? "";
        $this->mailer->Password = $smtp["password"] ?? "";
        $this->mailer->SMTPSecure = $smtp["secure"] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = $smtp["port"] ?? 587;
      } else {
        $this->mailer->isMail();
      }

      // From
      if(!empty($this->config["from"])) {
        $this->mailer->setFrom($this->config["from"], $this->config["fromName"] ?? "");
      }

      // Reply-To
      if(!empty($this->config["replyTo"])) {
        $this->mailer->addReplyTo(
          $this->config["replyTo"] ?? "",
          $this->config["replyToName"] ?? ""
        );
      }

      // CC
      if(!empty($this->config["cc"])) {
        foreach($this->config["cc"] as $cc) {
          $this->mailer->addCC($cc);
        }
      }

      // BCC
      if(!empty($this->config["bcc"])) {
        foreach($this->config["bcc"] as $bcc) {
          $this->mailer->addBCC($bcc);
        }
      }

      // Subject
      if(!empty($this->config["subject"])) {
        $this->mailer->Subject = $this->config["subject"];
      }

      // To
      if(!empty($this->config["to"])) {
        $this->mailer->addAddress($this->config["to"], $this->config["toName"] ?? "");
      }

      // Body
      if(!empty($this->config["bodyTemplate"])) {
        $twig = $this->getTwig();
        $template = $twig->createTemplate($this->config["bodyTemplate"]);
        $this->mailer->Body = $template->render(["data" => $data]);
      }

      // To field
      if(!empty($this->config["toField"]) && isset($data[$this->config["toField"]])) {
        $this->mailer->addAddress($data[$this->config["toField"]]);
      }

      // Send email
      $this->mailer->send();

    }catch(\PHPMailer\PHPMailer\Exception $e) {
      return $this->failure($this->mailer->ErrorInfo, ["errorObject" => $e]);
    }

    return $this->success();
  }

  private function getTwig(): \Twig\Environment {
    $loader = new \Twig\Loader\ArrayLoader([]);
    return new \Twig\Environment($loader);
  }
}
