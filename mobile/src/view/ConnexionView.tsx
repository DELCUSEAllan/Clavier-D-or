import {useState} from 'react';
import {Text, TextInput, View} from 'react-native';
import {SafeAreaView} from 'react-native-safe-area-context';
import { couleurs, styles } from './ConnexionView.styles';
import BoutonPrincipal from '@/components/BoutonPrincipal';

export default function ConnexionView() {
    const [email, setEmail] = useState('');
    const [motDePasse, setMotDePasse] = useState('');
    return (
        <SafeAreaView style={styles.ecran}>
            <Text style={styles.marque}>LE CLAVIER D’OR</Text>
            <Text style={styles.titre}>Content de te revoir</Text>
            <Text style={styles.sousTitre}>
                Connecte-toi pour reprendre ta progression.
            </Text>
            <Text style={styles.libelle}>Adresse e-mail</Text>
            <TextInput
                style={styles.champ}
                accessibilityLabel="Adresse e-mail"
                placeholder="nom@exemple.fr"
                placeholderTextColor={couleurs.secondaire}
                keyboardType="email-address"
                autoCapitalize="none"
                value={email}
                onChangeText={setEmail}
            />
            <Text style={styles.libelle}>Mot de passe</Text>
            <TextInput
                style={styles.champ}
                accessibilityLabel="Mot de passe"
                placeholder="Ton mot de passe"
                placeholderTextColor={couleurs.secondaire}
                autoCapitalize="none"
                secureTextEntry
                value={motDePasse}
                onChangeText={setMotDePasse}
            />
            <View style={styles.actions}>
                <BoutonPrincipal libelle="Se connecter" />
            </View>
        </SafeAreaView>
    );
}