import { StyleSheet } from 'react-native';

export const couleurs = {
    fond: '#0B0B0D',
    texte: '#F4F2ED',
    secondaire: '#A8ADB7',
};

const tailles = {
    petit: 14,
    texte: 15,
    saisie: 16,
    titre: 28,
};

export const styles = StyleSheet.create({
    ecran: {
        flex: 1,
        backgroundColor: couleurs.fond,
        paddingHorizontal: 24,
        paddingTop: 32,
    },
    marque: {
        color: '#E8B84A',
        fontSize: tailles.petit,
        fontWeight: '700',
        letterSpacing: 2,
    },
    titre: {
        color: couleurs.texte,
        fontSize: tailles.titre,
        fontWeight: '700',
        marginTop: 40,
    },
    sousTitre: {
        color: couleurs.secondaire,
        fontSize: tailles.texte,
        marginTop: 8,
    },
    libelle: {
        color: couleurs.texte,
        fontWeight: '600',
        marginTop: 24,
        marginBottom: 8,
    },
    champ: {
        minHeight: 54,
        backgroundColor: '#1C1D22',
        borderRadius: 14,
        paddingHorizontal: 16,
        color: couleurs.texte,
        fontSize: tailles.saisie,
    },
});