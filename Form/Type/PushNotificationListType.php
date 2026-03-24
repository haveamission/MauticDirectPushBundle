<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\Form\Type;

use MauticPlugin\MauticDirectPushBundle\Entity\PushNotificationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PushNotificationListType extends AbstractType
{
    public function __construct(
        private readonly PushNotificationRepository $repository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $notifications = $this->repository->findBy(['isPublished' => true], ['name' => 'ASC']);
        $choices = [];
        foreach ($notifications as $notification) {
            $choices[$notification->getName()] = $notification->getId();
        }

        $builder->add('notification', ChoiceType::class, [
            'label'       => 'mautic.direct_push.campaign.notification',
            'label_attr'  => ['class' => 'control-label'],
            'attr'        => ['class' => 'form-control'],
            'choices'     => $choices,
            'required'    => true,
            'placeholder' => 'mautic.direct_push.campaign.notification.placeholder',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'direct_push_notification_list';
    }
}
