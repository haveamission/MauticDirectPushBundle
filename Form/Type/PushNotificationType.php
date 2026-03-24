<?php

declare(strict_types=1);

namespace MauticPlugin\MauticDirectPushBundle\Form\Type;

use Mautic\CategoryBundle\Form\Type\CategoryListType;
use MauticPlugin\MauticDirectPushBundle\Entity\PushNotification;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PushNotificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label'      => 'mautic.core.name',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => true,
        ]);

        $builder->add('description', TextareaType::class, [
            'label'      => 'mautic.core.description',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control', 'rows' => 3],
            'required'   => false,
        ]);

        $builder->add('title', TextType::class, [
            'label'      => 'mautic.direct_push.form.title',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.direct_push.form.title.tooltip',
            ],
            'required'   => true,
        ]);

        $builder->add('body', TextareaType::class, [
            'label'      => 'mautic.direct_push.form.body',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'rows'    => 4,
                'tooltip' => 'mautic.direct_push.form.body.tooltip',
            ],
            'required'   => true,
        ]);

        $builder->add('url', UrlType::class, [
            'label'      => 'mautic.direct_push.form.url',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class'   => 'form-control',
                'tooltip' => 'mautic.direct_push.form.url.tooltip',
            ],
            'required'   => false,
        ]);

        $builder->add('imageUrl', UrlType::class, [
            'label'      => 'mautic.direct_push.form.image_url',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'required'   => false,
        ]);

        $builder->add('notificationType', ChoiceType::class, [
            'label'      => 'mautic.direct_push.form.type',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => ['class' => 'form-control'],
            'choices'    => [
                'mautic.direct_push.form.type.template' => 'template',
                'mautic.direct_push.form.type.list'     => 'list',
            ],
            'required'   => true,
        ]);

        $builder->add('category', CategoryListType::class, [
            'bundle' => 'plugin:directPush',
        ]);

        $builder->add('isPublished', ChoiceType::class, [
            'label'   => 'mautic.core.form.published',
            'choices' => [
                'mautic.core.form.published.yes' => true,
                'mautic.core.form.published.no'  => false,
            ],
            'attr'    => ['class' => 'form-control'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PushNotification::class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'direct_push_notification';
    }
}
