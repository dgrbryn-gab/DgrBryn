<?php

namespace App\Controller\Admin;

use App\Entity\StoreProduct;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class StoreProductCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return StoreProduct::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            TextField::new('name'),
            TextareaField::new('description')->hideOnIndex(),
            MoneyField::new('price')->setCurrency('USD'),
            TextField::new('image')->hideOnIndex(),
            BooleanField::new('isAvailable'),
            AssociationField::new('category'),
            DateTimeField::new('createdAt')->hideOnForm(),
        ];
    }
}


