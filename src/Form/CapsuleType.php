<?php

namespace App\Form;

use App\Entity\Capsule;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CapsuleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title')
            ->add('content')
            ->add('sendDate', null, [
                'widget' => 'single_text',
                'label' => 'Quand doit-elle s\'ouvrir ?',
            ])
            ->add('targetEmail', null, [
                'label' => 'Email du destinataire',
            ])
            // 👉 LE CHAMP IMAGE EST ICI :
            ->add('imageFile', FileType::class, [
                'label' => 'Ajouter une photo (Optionnel)',
                'mapped' => false, // Important : ne pas lier à l'entité directement
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M', // Taille max 5 Mo
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                        ],
                        'mimeTypesMessage' => 'Merci d\'uploader une image valide (JPG, PNG, WEBP)',
                    ])
                ],
                'attr' => ['class' => 'form-control'], // Joli style Bootstrap
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Capsule::class,
        ]);
    }
}
