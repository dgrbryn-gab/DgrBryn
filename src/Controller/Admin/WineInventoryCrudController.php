<?php

namespace App\Controller\Admin;

use App\Entity\WineInventory;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

class WineInventoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return WineInventory::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('product'),
            IntegerField::new('quantity'),
            DateField::new('acquiredDate')->setRequired(false),
            DateTimeField::new('lastUpdated')->hideOnForm(),
        ];
    }
}


