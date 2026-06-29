import 'package:flutter_test/flutter_test.dart';
import 'package:mobile_app/src/app.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  testWidgets('renderiza tela inicial', (WidgetTester tester) async {
    SharedPreferences.setMockInitialValues({});

    await tester.pumpWidget(const FullCareMobileApp());
    await tester.pumpAndSettle();

    expect(find.text('FullCare Audit'), findsOneWidget);
    expect(find.text('Entrar'), findsOneWidget);
  });
}
