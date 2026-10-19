<?php

/**
 * Catalogue des permissions WeightTrack + droits par défaut par profil.
 * Les modifications runtime sont stockées dans storage/app/acces_roles.json.
 */
return [

    'groupes' => [
        'analytiques' => [
            'label' => 'Analytiques',
            'permissions' => [
                'dashboard' => 'Tableau de bord',
            ],
        ],
        'flotte' => [
            'label' => 'Gestion Flotte PGF',
            'permissions' => [
                'flotte.voir' => 'Voir la flotte',
                'flotte.gerer' => 'Gérer les véhicules',
            ],
        ],
        'transferts' => [
            'label' => 'Gestion des transferts',
            'permissions' => [
                'transferts.liste' => 'Liste des transferts',
                'transferts.clients' => 'Clients',
                'transferts.financier' => 'Transferts financiers',
            ],
        ],
        'fiches_sortie' => [
            'label' => 'Fiches de sortie',
            'permissions' => [
                'fiches.liste' => 'Liste des fiches',
                'fiches.creer' => 'Créer une fiche',
                'fiches.modifier' => 'Modifier une fiche',
                'fiches.decharger' => 'Décharger une fiche',
            ],
        ],
        'tickets' => [
            'label' => 'Tickets',
            'permissions' => [
                'tickets.tous' => 'Tous les tickets',
                'tickets.locaux' => 'Tickets locaux',
                'tickets.en_attente' => 'Tickets en attente',
                'tickets.valider' => 'Valider un ticket',
                'tickets.unipalm' => 'Tickets Unipalm',
                'tickets.modifier' => 'Modifier un ticket',
                'tickets.supprimer' => 'Supprimer un ticket',
                'tickets.pdf' => 'Exporter bordereau PDF',
            ],
        ],
        'ponts' => [
            'label' => 'Gestion ponts PGF',
            'permissions' => [
                'ponts.liste' => 'Liste des ponts',
                'ponts.gerer' => 'Gérer les ponts',
                'ponts.stocks' => 'Stocks par pont',
            ],
        ],
        'pesage' => [
            'label' => 'Pesage',
            'permissions' => [
                'pesage.liste' => 'Liste des pesées',
                'pesage.creer' => 'Enregistrer une pesée',
            ],
        ],
        'finances' => [
            'label' => 'Gestion financière',
            'permissions' => [
                'finances.vue' => 'Vue financière',
                'finances.montant_pisteur' => 'Montant pisteur PGF',
                'finances.montant_hors_pgf' => 'Montant hors PGF',
                'finances.montant_particulier' => 'Montant particulier',
                'finances.sorties' => 'Sorties financières',
                'finances.recus' => 'Reçus de paiement',
                'finances.salaires' => 'Salaires chauffeurs',
            ],
        ],
        'paiements' => [
            'label' => 'Gestion paiements',
            'permissions' => [
                'paiements.effectuer' => 'Effectuer un paiement',
                'paiements.historique' => 'Historique des paiements',
            ],
        ],
        'pisteurs' => [
            'label' => 'Pisteurs',
            'permissions' => [
                'pisteurs.pgf' => 'Gestion des Pisteurs PGF',
                'pisteurs.autres' => 'Gestion Autres Pisteurs',
            ],
        ],
        'chargeurs' => [
            'label' => 'Chargeurs',
            'permissions' => [
                'chargeurs.chefs' => 'Chef des chargeurs',
                'chargeurs.liste' => 'Chargeurs',
            ],
        ],
        'chauffeurs_transporteurs' => [
            'label' => 'Chauffeurs & Transporteurs',
            'permissions' => [
                'chauffeurs.gerer' => 'Gestion Chauffeurs',
                'transporteurs.gerer' => 'Gestion Transporteurs',
                'bilan.camion' => 'Bilan par camion',
            ],
        ],
        'usines' => [
            'label' => 'Usines',
            'permissions' => [
                'usines.gerer' => 'Gestion des usines',
            ],
        ],
        'produits' => [
            'label' => 'Produits',
            'permissions' => [
                'produits.voir' => 'Voir les produits',
                'produits.creer' => 'Créer un produit',
                'produits.modifier' => 'Modifier un produit',
                'produits.supprimer' => 'Supprimer un produit',
            ],
        ],
        'utilisateurs' => [
            'label' => 'Gestion des utilisateurs',
            'permissions' => [
                'utilisateurs.liste' => 'Liste des utilisateurs',
                'utilisateurs.admins' => 'Administrateurs',
                'utilisateurs.gestionnaires' => 'Gestionnaires',
                'utilisateurs.commis' => 'Commis de pont',
                'utilisateurs.agents' => 'Agents',
                'utilisateurs.chauffeurs' => 'Chauffeurs (comptes)',
                'utilisateurs.creer' => 'Créer / modifier un utilisateur',
            ],
        ],
        'fournisseurs_parcs' => [
            'label' => 'Fournisseurs & Parcs',
            'permissions' => [
                'fournisseurs.gerer' => 'Fournisseurs',
                'parcs.gerer' => 'Parcs',
            ],
        ],
        'appro_caisse' => [
            'label' => 'Approvisionnements & Caisse',
            'permissions' => [
                'approvisionnements.gerer' => 'Approvisionnements',
                'caisse.gerer' => 'Gestion de caisse',
            ],
        ],
        'systeme' => [
            'label' => 'Système',
            'permissions' => [
                'acces.gerer' => 'Gestion des accès / rôles',
                'parametres.systeme' => 'Paramètres système',
            ],
        ],
    ],

    'profils' => [
        'superadmin' => [
            'label' => 'Super administrateur',
            'description' => 'Utilisateur caché — accès complet',
            'couleur' => 'danger',
            'permissions' => '*',
        ],
        'pgf' => [
            'label' => 'Chef PGF',
            'description' => 'Gestion complète de la plateforme',
            'couleur' => 'primary',
            'permissions' => [
                'dashboard',
                'flotte.voir', 'flotte.gerer',
                'transferts.liste', 'transferts.clients', 'transferts.financier',
                'fiches.liste', 'fiches.creer', 'fiches.modifier', 'fiches.decharger',
                'tickets.tous', 'tickets.locaux', 'tickets.en_attente', 'tickets.valider',
                'tickets.unipalm', 'tickets.modifier', 'tickets.supprimer', 'tickets.pdf',
                'ponts.liste', 'ponts.gerer', 'ponts.stocks',
                'pesage.liste', 'pesage.creer',
                'finances.vue', 'finances.montant_pisteur', 'finances.montant_hors_pgf',
                'finances.montant_particulier', 'finances.sorties', 'finances.recus', 'finances.salaires',
                'paiements.effectuer', 'paiements.historique',
                'pisteurs.pgf', 'pisteurs.autres',
                'chargeurs.chefs', 'chargeurs.liste',
                'chauffeurs.gerer', 'transporteurs.gerer', 'bilan.camion',
                'usines.gerer',
                'produits.voir', 'produits.creer', 'produits.modifier', 'produits.supprimer',
                'utilisateurs.liste', 'utilisateurs.admins', 'utilisateurs.gestionnaires',
                'utilisateurs.commis', 'utilisateurs.agents', 'utilisateurs.chauffeurs', 'utilisateurs.creer',
                'fournisseurs.gerer', 'parcs.gerer',
                'approvisionnements.gerer', 'caisse.gerer',
                'acces.gerer',
            ],
        ],
        'gestionnaire' => [
            'label' => 'Gestionnaire',
            'description' => 'Même périmètre métier que PGF',
            'couleur' => 'info',
            'permissions' => [
                'dashboard',
                'flotte.voir', 'flotte.gerer',
                'transferts.liste', 'transferts.clients', 'transferts.financier',
                'fiches.liste', 'fiches.creer', 'fiches.modifier', 'fiches.decharger',
                'tickets.tous', 'tickets.locaux', 'tickets.en_attente', 'tickets.valider',
                'tickets.unipalm', 'tickets.modifier', 'tickets.supprimer', 'tickets.pdf',
                'ponts.liste', 'ponts.gerer', 'ponts.stocks',
                'pesage.liste', 'pesage.creer',
                'finances.vue', 'finances.montant_pisteur', 'finances.montant_hors_pgf',
                'finances.montant_particulier', 'finances.sorties', 'finances.recus', 'finances.salaires',
                'paiements.effectuer', 'paiements.historique',
                'pisteurs.pgf', 'pisteurs.autres',
                'chargeurs.chefs', 'chargeurs.liste',
                'chauffeurs.gerer', 'transporteurs.gerer', 'bilan.camion',
                'usines.gerer',
                // Création seule : pas de modification / suppression produit
                'produits.voir', 'produits.creer',
                'utilisateurs.liste', 'utilisateurs.admins', 'utilisateurs.gestionnaires',
                'utilisateurs.commis', 'utilisateurs.agents', 'utilisateurs.chauffeurs', 'utilisateurs.creer',
                'fournisseurs.gerer', 'parcs.gerer',
                'approvisionnements.gerer', 'caisse.gerer',
            ],
        ],
        'chef_equipe' => [
            'label' => 'Chef d’équipe',
            'description' => 'Périmètre limité à son équipe',
            'couleur' => 'warning',
            'permissions' => [
                'dashboard',
                'flotte.voir',
                'fiches.liste', 'fiches.creer', 'fiches.modifier', 'fiches.decharger',
                'tickets.tous', 'tickets.en_attente', 'tickets.valider', 'tickets.pdf',
                'ponts.liste', 'ponts.stocks',
                'pesage.liste', 'pesage.creer',
                'finances.vue', 'finances.montant_pisteur', 'finances.recus',
                'paiements.effectuer', 'paiements.historique',
                'pisteurs.pgf',
                'chargeurs.chefs', 'chargeurs.liste',
                'chauffeurs.gerer', 'transporteurs.gerer', 'bilan.camion',
                'usines.gerer',
                'produits.voir',
                'caisse.gerer',
            ],
        ],
        'admin' => [
            'label' => 'Administrateur',
            'description' => 'Gestion des comptes et référentiels',
            'couleur' => 'secondary',
            'permissions' => [
                'dashboard',
                'utilisateurs.liste', 'utilisateurs.admins', 'utilisateurs.gestionnaires',
                'utilisateurs.commis', 'utilisateurs.agents', 'utilisateurs.chauffeurs', 'utilisateurs.creer',
                'fournisseurs.gerer', 'parcs.gerer',
                'usines.gerer',
                'produits.voir', 'produits.creer',
            ],
        ],
        'commis' => [
            'label' => 'Commis de pont',
            'description' => 'Opérations sur son pont',
            'couleur' => 'success',
            'permissions' => [
                'dashboard',
                'pesage.liste', 'pesage.creer',
                'fiches.liste', 'fiches.creer', 'fiches.modifier',
                'tickets.en_attente', 'tickets.valider',
            ],
        ],
        'agent' => [
            'label' => 'Agent / Pisteur',
            'description' => 'Suivi de son activité',
            'couleur' => 'dark',
            'permissions' => [
                'dashboard',
                'tickets.tous',
            ],
        ],
        'driver' => [
            'label' => 'Chauffeur',
            'description' => 'Consultation limitée',
            'couleur' => 'secondary',
            'permissions' => [
                'dashboard',
            ],
        ],
    ],

];
