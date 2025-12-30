<?php

namespace App\Controller\Admin;

use App\Entity\Capsule;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;

class CapsuleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Capsule::class;
    }

    // 🔒 SÉCURITÉ MAXIMALE : JUSTE SUPPRIMER
    public function configureActions(Actions $actions): Actions
    {
        return $actions
            // On désactive TOUT sauf DELETE (qui est activé par défaut)
            ->disable(Action::NEW, Action::EDIT, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        // On cache l'ID (inutile)
        yield IdField::new('id')->hideOnForm()->hideOnIndex();

        // On affiche juste les infos "extérieures" (Métadonnées)
        yield TextField::new('targetEmail', 'Email Destinataire');
        yield TextField::new('title', 'Titre');

        // ✅ CORRECTION DATE : On utilise le bon nom 'sendDate'
        yield DateTimeField::new('sendDate', 'Date d\'envoi');

        // ✅ CORRECTION MESSAGE : On utilise 'content' (même si on ne l'affiche pas, c'est pour être propre)
        // Mais comme tu ne veux rien voir, on n'a même pas besoin de mettre les champs Image ou Content ici !

        yield BooleanField::new('isSent', 'Envoyé ?')
            ->renderAsSwitch(false);
    }
}
