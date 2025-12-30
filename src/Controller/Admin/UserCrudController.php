<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
       

        // On met l'email en lecture seule sur la page d'édition pour éviter les bêtises
        yield EmailField::new('email')->setDisabled();

        yield BooleanField::new('isVerified', 'Compte Vérifié');

        // 👉 LE MENU POUR DONNER LE GRADE ADMIN
        yield ChoiceField::new('roles', 'Rôles')
            ->setChoices([
                'Utilisateur' => 'ROLE_USER',
                'Administrateur' => 'ROLE_ADMIN'
            ])
            ->allowMultipleChoices()
            ->renderExpanded(); // Affiche des cases à cocher
    }
}
