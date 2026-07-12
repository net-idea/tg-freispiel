<?php
declare(strict_types=1);

namespace App\Form;

use App\Entity\FormRegistrationEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label'          => 'Name',
                'required'       => true,
                'empty_data'     => '',
                'error_bubbling' => false,
                'attr'           => [
                    'autocomplete' => 'name',
                    'maxlength'    => 120,
                    'class'        => 'form-control',
                ],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('email', EmailType::class, [
                'label'          => 'E‑Mail‑Adresse',
                'required'       => true,
                'empty_data'     => '',
                'property_path'  => 'emailAddress',
                'error_bubbling' => false,
                'attr'           => [
                    'autocomplete' => 'email',
                    'maxlength'    => 200,
                    'class'        => 'form-control',
                ],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('phone', TelType::class, [
                'label'          => 'Telefonnummer',
                'required'       => true,
                'empty_data'     => '',
                'error_bubbling' => false,
                'attr'           => [
                    'autocomplete' => 'tel',
                    'maxlength'    => 40,
                    'class'        => 'form-control',
                ],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('motivation', TextareaType::class, [
                'label'          => 'Was motiviert dich mitzumachen?',
                'required'       => true,
                'empty_data'     => '',
                'error_bubbling' => false,
                'attr'           => [
                    'rows'      => 4,
                    'maxlength' => 2000,
                    'class'     => 'form-control',
                ],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('roleTypes', ChoiceType::class, [
                'label'          => 'Wenn du dir eine Rolle aussuchen könntest, wäre sie …',
                'required'       => true,
                'multiple'       => true,
                'expanded'       => true,
                'error_bubbling' => false,
                'choices'        => array_combine(
                    FormRegistrationEntity::ROLE_TYPES,
                    FormRegistrationEntity::ROLE_TYPES
                ),
                'choice_attr' => static fn () => ['class' => 'form-check-input'],
                'label_attr'  => ['class' => 'form-label'],
            ])
            ->add('roleReason', TextareaType::class, [
                'label'          => 'Und warum?',
                'required'       => true,
                'empty_data'     => '',
                'error_bubbling' => false,
                'attr'           => [
                    'rows'      => 4,
                    'maxlength' => 2000,
                    'class'     => 'form-control',
                ],
                'label_attr' => ['class' => 'form-label'],
            ])
            ->add('expectations', TextareaType::class, [
                'label'          => 'Was erwartest du von uns?',
                'required'       => true,
                'empty_data'     => '',
                'error_bubbling' => false,
                'attr'           => [
                    'rows'      => 4,
                    'maxlength' => 2000,
                    'class'     => 'form-control',
                ],
                'label_attr' => ['class' => 'form-label'],
            ])
            // consent must be true
            ->add('consent', CheckboxType::class, [
                'label'          => 'Ich willige in die Verarbeitung meiner Angaben zum Zweck der Anmeldung ein.',
                'required'       => true,
                'mapped'         => true,
                'error_bubbling' => false,
                'attr'           => ['class' => 'form-check-input'],
                'label_attr'     => ['class' => 'form-check-label'],
            ])
            ->add('copy', CheckboxType::class, [
                'label'      => 'Kopie an mich senden',
                'required'   => false,
                'attr'       => ['class' => 'form-check-input'],
                'label_attr' => ['class' => 'form-check-label'],
            ])
            // spam traps
            ->add('emailrep', TextType::class, [
                'label'      => false,
                'required'   => false,
                'empty_data' => '',
                'attr'       => [
                    'autocomplete' => 'off',
                    'tabindex'     => '-1',
                    'class'        => 'visually-hidden',
                    'aria-hidden'  => 'true',
                    'style'        => 'position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;',
                ],
            ])
            ->add('website', TextType::class, [
                'label'      => false,
                'mapped'     => false,
                'required'   => false,
                'empty_data' => '',
                'attr'       => [
                    'autocomplete' => 'off',
                    'tabindex'     => '-1',
                    'class'        => 'visually-hidden',
                    'aria-hidden'  => 'true',
                    'style'        => 'position:absolute;left:-10000px;top:auto;width:1px;height:1px;overflow:hidden;',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'      => FormRegistrationEntity::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id'   => 'form_registration',
        ]);
    }
}
