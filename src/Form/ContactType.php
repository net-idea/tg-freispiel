<?php

namespace App\Form;

use App\Entity\Contact;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ihr Name'],
                'constraints' => [
                    new NotBlank(['message' => 'Bitte geben Sie Ihren Namen ein.']),
                    new Length(['min' => 2, 'max' => 255]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-Mail',
                'attr' => ['class' => 'form-control', 'placeholder' => 'ihre.email@beispiel.de'],
                'constraints' => [
                    new NotBlank(['message' => 'Bitte geben Sie Ihre E-Mail-Adresse ein.']),
                    new Email(['message' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.']),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Nachricht',
                'attr' => ['class' => 'form-control', 'rows' => 5, 'placeholder' => 'Ihre Nachricht an uns...'],
                'constraints' => [
                    new NotBlank(['message' => 'Bitte geben Sie eine Nachricht ein.']),
                    new Length(['min' => 10, 'max' => 5000]),
                ],
            ])
            // Honeypot field - should remain empty
            ->add('website', TextType::class, [
                'label' => false,
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'class' => 'visually-hidden',
                    'tabindex' => '-1',
                    'autocomplete' => 'off',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Contact::class,
        ]);
    }
}
