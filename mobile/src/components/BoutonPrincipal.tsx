import {Pressable, StyleSheet, Text} from 'react-native';


type BoutonPrincipalProps = {
    libelle: string;
    onPress?: () => void;
    desactive?: boolean;
};

export default function BoutonPrincipal({
    libelle,
    onPress,
    desactive = false,
    }: BoutonPrincipalProps) {

    const estDesactive = desactive || !onPress;

    return (
        <Pressable
            accessibilityRole="button"
            accessibilityState={{disabled: estDesactive}}
            disabled={estDesactive}
            onPress={onPress}
            style={[styles.bouton, estDesactive && styles.desactive]}
        >
            <Text style={styles.texte}>{libelle}</Text>
        </Pressable>
    );
}


// Styles appliqués au bouton
const styles = StyleSheet.create({
    bouton: {
        minHeight: 54,
        paddingHorizontal: 16,
        paddingVertical: 12,
        borderRadius: 14,
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: '#1857CC',
    },
    desactive: {
        backgroundColor: '#1C1D22',
    },
    texte: {
        color: '#F4F2ED',
        fontSize: 16,
        fontWeight: '700',
        textAlign: 'center',
    },
});