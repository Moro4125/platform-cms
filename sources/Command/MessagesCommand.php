<?php
/**
 * Class MessagesCommand
 *
 * Cron: vendor/bin/platform messages --send -q
 */
namespace Moro\Platform\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MessagesCommand
 * @package Moro\Platform\Command
 */
class MessagesCommand extends AbstractCommand
{
	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this->setName(defined('CLI_NAMESPACE_PLATFORM') ? 'messages' : 'platform:messages')
			->setDescription('Work with users notifications.')
			->addOption('send', null, InputOption::VALUE_OPTIONAL, 'Send messages to subscribers.', false)
			->ignoreValidationErrors();
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return null|int null or 0 if everything went fine, or an error code
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if ($output->getVerbosity() == OutputInterface::VERBOSITY_QUIET)
		{
			$output->setVerbosity(OutputInterface::VERBOSITY_NORMAL);
		}

		$flag = false;

		if ($this->hasArgumentFlag('send'))
		{
			$flag = true;
			$this->doSend($output);
		}

		if (!$flag)
		{
			/** @noinspection HtmlUnknownTag */
			$output->writeln('<error>Known actions: send.</error>');
		}

		return null;
	}

	/**
	 * @param OutputInterface $output
	 */
	protected function doSend(OutputInterface $output)
	{
		$app = $this->getPlatformApplication();
		$verbose = ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL);
		$serviceFile = $app->getServiceFile();
		$serviceMailer = $app->getServiceMailer();
		$serviceMessages = $app->getServiceMessages();
		$serviceSubscribers = $app->getServiceSubscribers();

		$startingLine = $serviceSubscribers->getStartingLine();
		$finishLine   = $serviceMessages->getFinishLine();
		$prefixLength = strlen('подписка:');

		if ($startingLine >= $finishLine)
		{
			$msg = 'Nothing to do (subscriber line %1$s, message line %2$s).';
			$format = 'Y.d.m H:i:s \\G\\M\\T';
			$verbose && $output->writeln(sprintf($msg, gmdate($format, $startingLine), gmdate($format, $finishLine)));
			return;
		}

		$from = $app->getOption('notification.from.email');
		$host = explode('@', $from)[1];
		$limit = $app->getOption('notification.messages.limit');
		$stepLimit = $app->getOption('notification.step.limit');
		$messages = [];

		foreach ($serviceMessages->selectEntitiesByStartingLine($startingLine, $limit) as $message)
		{
			foreach (array_map('normalizeTag', $message->getTags()) as $tag)
			{
				if (strncmp($tag, 'подписка:', $prefixLength) === 0)
				{
					$messages[$tag][] = $message;
				}
			}
		}

		$server = $app->getOption('mailer.host').':'.$app->getOption('mailer.port');
		$verbose && $output->writeln('Connect to SMTP server '.$server);
		$serviceMailer->getTransport()->start();

		foreach ($serviceSubscribers->selectEntitiesByFinishLine($finishLine, $stepLimit) as $subscriber)
		{
			$tags = [];

			foreach ($subscriber->getTags() as $tag)
			{
				$normalizedTag = normalizeTag($tag);

				if (strncmp($normalizedTag, 'подписка:', $prefixLength) === 0)
				{
					$tags[$normalizedTag] = trim(substr($tag, strpos($tag, ':') + 1));
				}
			}

			$notifications = [];

			foreach (array_intersect_key($messages, $tags) as $list)
			{
				$notifications = array_unique(array_merge($notifications, $list), SORT_REGULAR);
			}

			$address = $subscriber->getEmail();
			$cancel = $app->url('api-subscribers-delete', [
				'user' => $address,
				'roles' => 'ROLE_WANT_DELETE_SUBSCRIBER',
				'counter' => 1,
			]);

			$tags = implode(', ', $tags);
			$content = ''
				. "*Письмо отправлено Вам сайтом $host в рамках рассылки по подписке: $tags.*  ".PHP_EOL
				. "*Если хотите отписаться от данной рассылки, то перейдите по [этой ссылке]($cancel)*".PHP_EOL.PHP_EOL
				. "С уважением,  ".PHP_EOL."Администрация сайта [$host](http://$host)";

			/** @var \Swift_Message $mail */
			$mail = $serviceMailer->createMessage();

			/** @var \Moro\Platform\Model\Implementation\Messages\MessagesInterface $notification */
			foreach ($notifications as $notification)
			{
				$verbose && $output->writeln('Prepare message with ID:'.$notification->getId().' for '.$address);

				$content = '-----'."\n\n$content";
				$content = '# '.$notification->getName()."\n\n".$notification->selectParameter('text')."\n\n$content";

				foreach ($serviceMessages->selectAttachmentByEntity($notification) as $attachment)
				{
					$verbose && $output->writeln('Add attachment: '.$attachment->getName());

					$path = $serviceFile->getPathForHash($attachment->getHash());
					$file = \Swift_Attachment::fromPath($path);
					$file->setFilename($attachment->getName());

					$mail->attach($file);
				}
			}

			$subscriber->setOrderAt($finishLine);
			$serviceSubscribers->commit($subscriber);

			if ($count = count($notifications))
			{
				$verbose && $output->writeln('Send message to '.$address);

				$text = $app->getTwigExtensionMarkdown()->cleanMarkdown($content);
				$html = $app->getTwigExtensionMarkdown()->parseMarkdown($content);

				$mail->setFrom($from);
				$mail->setTo($address);
				$mail->setSubject(($count == 1) ? $notification->getName() : "Пакет оповещений ($count шт.)");
				$mail->addPart($text, 'text/plain', 'utf-8');
				$mail->addPart($html, 'text/html',  'utf-8');
				$serviceMailer->send($mail);
			}
		}

		$serviceMailer->getTransport()->stop();
	}
}