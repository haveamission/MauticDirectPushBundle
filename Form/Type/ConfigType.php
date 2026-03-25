<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('direct_push_enabled', YesNoButtonGroupType::class, [
            'label'      => 'mautic.direct_push.config.enabled',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control', 'tooltip' => 'mautic.direct_push.config.enabled.tooltip'],
            'required'   => false,
        ]);

        $builder->add('direct_push_fcm_enabled', YesNoButtonGroupType::class, [
            'label'      => 'mautic.direct_push.config.fcm.enabled',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => false,
        ]);

        $builder->add('direct_push_fcm_service_account', TextareaType::class, [
            'label'      => 'mautic.direct_push.config.fcm.service_account',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'       => 'form-control',
                'rows'        => 6,
                'tooltip'     => 'mautic.direct_push.config.fcm.service_account.tooltip',
                'placeholder' => 'mautic.direct_push.config.fcm.service_account.placeholder',
            ],
            'required'   => false,
        ]);

        $builder->add('direct_push_fcm_project_id', TextType::class, [
            'label'      => 'mautic.direct_push.config.fcm.project_id',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.direct_push.config.fcm.project_id.tooltip',
            ],
            'required'   => false,
        ]);

        $builder->add('direct_push_apns_enabled', YesNoButtonGroupType::class, [
            'label'      => 'mautic.direct_push.config.apns.enabled',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => false,
        ]);

        $builder->add('direct_push_apns_key_contents', TextareaType::class, [
            'label'      => 'mautic.direct_push.config.apns.key_contents',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'       => 'form-control',
                'rows'        => 6,
                'tooltip'     => 'mautic.direct_push.config.apns.key_contents.tooltip',
                'placeholder' => 'mautic.direct_push.config.apns.key_contents.placeholder',
            ],
            'required'   => false,
        ]);

        $builder->add('direct_push_apns_key_id', TextType::class, [
            'label'      => 'mautic.direct_push.config.apns.key_id',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => false,
        ]);

        $builder->add('direct_push_apns_team_id', TextType::class, [
            'label'      => 'mautic.direct_push.config.apns.team_id',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => false,
        ]);

        $builder->add('direct_push_apns_bundle_id', TextType::class, [
            'label'      => 'mautic.direct_push.config.apns.bundle_id',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => false,
        ]);

        $builder->add('direct_push_apns_production', YesNoButtonGroupType::class, [
            'label'      => 'mautic.direct_push.config.apns.production',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control', 'tooltip' => 'mautic.direct_push.config.apns.production.tooltip'],
            'required'   => false,
        ]);

        $builder->add('direct_push_batch_size', IntegerType::class, [
            'label'      => 'mautic.direct_push.config.batch_size',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'direct_push_config';
    }
}
